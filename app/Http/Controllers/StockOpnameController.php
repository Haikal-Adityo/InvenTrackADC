<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockOpname;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock Opname (tab di Rekap Stok).
 * Staf/admin input hasil hitung fisik → admin verifikasi → selisih dibuat transaksi penyesuaian (approved).
 */
class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $opnames = StockOpname::with(['user', 'verifier', 'lines.item', 'lines.adjustment'])
            ->visibleFor($user)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('counted_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $pendingCount = StockOpname::visibleFor($user)->where('status', 'submitted')->count();

        return view('stock.opname', compact('opnames', 'pendingCount'));
    }

    public function create()
    {
        $this->authorizeInput();

        $items = Item::visibleFor(auth()->user())->orderBy('category')->orderBy('name')->get()
            ->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category ?: 'Tanpa Kategori',
                'unit' => $item->unit,
                'stock' => $item->current_stock,
            ]);

        return view('stock.opname-create', ['items' => $items]);
    }

    public function store(Request $request)
    {
        $this->authorizeInput();

        $validated = $request->validate([
            'counted_at' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'lines' => 'required|array',
            'lines.*.item_id' => 'required|integer',
            'lines.*.actual_stock' => 'nullable|integer|min:0',
            'lines.*.notes' => 'nullable|string|max:500',
        ]);

        $filled = collect($validated['lines'])->filter(fn ($l) => isset($l['actual_stock']) && $l['actual_stock'] !== '');
        if ($filled->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Isi stok aktual minimal untuk satu barang.']);
        }

        $items = Item::visibleFor(auth()->user())->whereIn('id', $filled->pluck('item_id'))->get()->keyBy('id');
        if ($items->count() !== $filled->pluck('item_id')->unique()->count()) {
            throw ValidationException::withMessages(['lines' => 'Ada barang yang tidak sesuai dengan bidang Anda.']);
        }

        $opname = DB::transaction(function () use ($validated, $filled, $items) {
            $opname = StockOpname::create([
                'bidang' => auth()->user()->bidang ?? 'umum',
                'user_id' => auth()->id(),
                'counted_at' => $validated['counted_at'],
                'status' => 'submitted',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($filled as $line) {
                $item = $items->get((int) $line['item_id']);
                $system = $item->current_stock;
                $actual = (int) $line['actual_stock'];

                $opname->lines()->create([
                    'item_id' => $item->id,
                    'system_stock' => $system,
                    'actual_stock' => $actual,
                    'difference' => $actual - $system,
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $opname;
        });

        \App\Support\InventoryNotifier::stockOpnameBaru($opname);
        \App\Support\InventoryMail::sendTo(
            \App\Support\InventoryMail::adminNotificationRecipients($opname->bidang),
            new \App\Mail\StockOpnameSubmitted($opname),
            'Stok Opname baru',
            ['stock_opname_id' => $opname->id]
        );
        \App\Models\ActivityLog::record('stock_opname_submitted', 'Stock opname '.$opname->document_number.' diajukan ('.$filled->count().' barang)', null, $opname->document_number);

        return redirect()->route('stock.opname.index')
            ->with('success', "Stock opname {$opname->document_number} ({$filled->count()} barang) berhasil disimpan dan menunggu verifikasi admin.");
    }

    public function verify(StockOpname $stockOpname)
    {
        $this->authorizeVerifier($stockOpname);

        $adjusted = 0;
        try {
        DB::transaction(function () use ($stockOpname, &$adjusted) {
            // Kunci baris SO: klik ganda / dua admin bersamaan tidak boleh membuat penyesuaian dua kali
            $locked = StockOpname::whereKey($stockOpname->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'submitted') {
                throw new \RuntimeException('Stock opname ini sudah diproses.');
            }

            $stockOpname->load('lines.item');

            foreach ($stockOpname->lines as $line) {
                if ((int) $line->difference === 0 || ! $line->item) {
                    continue;
                }

                $tx = Transaction::create([
                    'item_id' => $line->item_id,
                    'user_id' => auth()->id(),
                    'bidang' => $stockOpname->bidang,
                    'date' => $stockOpname->counted_at->toDateString(),
                    'type' => $line->difference > 0 ? 'in' : 'out',
                    'quantity' => abs((int) $line->difference),
                    'volume' => abs((int) $line->difference),
                    'price' => null,
                    'description' => 'Penyesuaian Stock Opname '.$stockOpname->document_number.($line->notes ? ' — '.$line->notes : ''),
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                $line->update(['adjustment_transaction_id' => $tx->id]);
                $adjusted++;
            }

            $stockOpname->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        \App\Support\InventoryNotifier::stockOpnameDiproses($stockOpname);
        \App\Support\InventoryNotifier::cekStokRendah($stockOpname->lines->pluck('item_id'));

        return back()->with('success', "Stock opname {$stockOpname->document_number} terverifikasi. {$adjusted} transaksi penyesuaian stok dibuat.");
    }

    public function reject(StockOpname $stockOpname)
    {
        $this->authorizeVerifier($stockOpname);

        $affected = StockOpname::whereKey($stockOpname->id)->where('status', 'submitted')->update([
            'status' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'updated_at' => now(),
        ]);
        if ($affected === 0) {
            return back()->with('error', 'Stock opname ini sudah diproses.');
        }
        $stockOpname->refresh();

        \App\Support\InventoryNotifier::stockOpnameDiproses($stockOpname);

        return back()->with('success', "Stock opname {$stockOpname->document_number} ditolak. Stok tidak diubah.");
    }

    private function authorizeInput(): void
    {
        $user = auth()->user();
        abort_unless(($user->isStaff() || $user->isAdmin()) && ! $user->isTeknik(), 403, 'Hanya staf/admin bidang Umum yang dapat melakukan stock opname.');
    }

    private function authorizeVerifier(StockOpname $opname): void
    {
        $user = auth()->user();
        abort_unless($user->isAdmin() && $user->canAccessBidang($opname->bidang), 403, 'Hanya admin yang dapat memverifikasi stock opname.');
    }
}
