<?php

namespace App\Http\Controllers;

use App\Exports\StockRequestExport;
use App\Mail\StockRequestApproved;
use App\Mail\StockRequestSubmitted;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StockRequestLine;
use App\Support\InventoryMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class StockRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = StockRequest::with(['lines.item', 'user', 'processor'])
            ->visibleFor(auth()->user())
            ->latest();

        if (auth()->user()->isStaff() || (auth()->user()->isAdmin() && auth()->user()->isTeknik())) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where(function ($q) use ($request) {
                $q->where('category', $request->category)
                    ->orWhereHas('lines.item', fn($lineQuery) => $lineQuery->where('category', $request->category));
            });
        }

        $stockRequests = $query->paginate(15)->withQueryString();
        $categories = Item::visibleFor(auth()->user())->select('category')->distinct()->orderBy('category')->pluck('category');
        $pendingCount = StockRequest::visibleFor(auth()->user())->pending()->count();

        $yearExpr = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', created_at) as year" : 'YEAR(created_at) as year';
        $years = StockRequest::visibleFor(auth()->user())
            ->selectRaw($yearExpr)
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        return view('stock-requests.index', compact('stockRequests', 'categories', 'pendingCount', 'years'));
    }

    public function store(Request $request)
    {
        abort_unless(
            auth()->user()->isStaff() || (auth()->user()->isAdmin() && auth()->user()->isTeknik()),
            403,
            'Anda tidak memiliki akses untuk membuat stock request.'
        );

        $validated = $request->validate([
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|distinct|exists:items,id',
            'lines.*.price' => 'required|integer|min:0',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.description' => 'nullable|string|max:500',
        ], [
            'lines.required' => 'Tambahkan minimal satu barang.',
            'lines.*.item_id.distinct' => 'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
            'lines.*.quantity.min' => 'Jumlah request minimal 1.',
        ]);

        $requestedItemIds = collect($validated['lines'])
            ->pluck('item_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $allowedItemIds = Item::visibleFor(auth()->user())
            ->whereIn('id', $requestedItemIds)
            ->pluck('id')
            ->map(fn($id) => (int) $id);

        if ($allowedItemIds->count() !== $requestedItemIds->count()) {
            throw ValidationException::withMessages([
                'lines' => 'Ada barang yang tidak sesuai dengan bidang Anda.',
            ]);
        }

        $pendingItems = StockRequestLine::with('item')
            ->whereIn('item_id', $requestedItemIds)
            ->whereHas('stockRequest', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('status', 'pending');
            })
            ->get()
            ->pluck('item.name')
            ->filter()
            ->unique()
            ->values();

        if ($pendingItems->isNotEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Barang berikut masih memiliki request stok pending: ' . $pendingItems->implode(', ') . '. Tunggu diproses atau hapus dari form.',
            ]);
        }

        $createdStockRequests = DB::transaction(function () use ($validated) {
            $items = Item::visibleFor(auth()->user())
                ->whereIn('id', collect($validated['lines'])->pluck('item_id'))
                ->get()
                ->keyBy('id');
            $timestamp = now();
            $groupedLines = collect($validated['lines'])
                ->map(function ($line) use ($items) {
                    $item = $items->get((int) $line['item_id']);

                    return [
                        'item' => $item,
                        'line' => $line,
                        'category' => $item?->category ?: 'Tanpa Kategori',
                    ];
                })
                ->groupBy('category');

            $createdStockRequests = collect();

            foreach ($groupedLines as $category => $rows) {
                $stockRequest = new StockRequest([
                    'user_id' => auth()->id(),
                    'bidang' => $rows->first()['item']?->bidang ?? auth()->user()->bidang,
                    'category' => $category,
                    'status' => 'pending',
                ]);
                $stockRequest->created_at = $timestamp;
                $stockRequest->updated_at = $timestamp;
                $stockRequest->save();

                foreach ($rows as $row) {
                    $line = $row['line'];
                    $item = $row['item'];

                    $stockRequest->lines()->create([
                        'item_id' => $line['item_id'],
                        'category' => $item?->category,
                        'price' => (int) $line['price'],
                        'quantity' => (int) $line['quantity'],
                        'description' => $line['description'] ?? null,
                    ]);
                }

                $createdStockRequests->push($stockRequest);
            }

            return $createdStockRequests;
        });

        $recipients = InventoryMail::gaadmNotificationRecipients();
        if ($recipients === []) {
            Log::warning('Email Request Stock Barang tidak dikirim: GAADM_NOTIFICATION_MAIL belum diset di .env.');
        } else {
            foreach ($recipients as $email) {
                try {
                    Mail::to($email)->send(new StockRequestSubmitted($createdStockRequests));
                } catch (\Throwable $e) {
                    Log::error('Gagal kirim email Request Stock Barang', [
                        'to' => $email,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('stock-requests.index')
            ->with('success', "{$createdStockRequests->count()} Permintaan Stok Barang berhasil dibuat.");
    }

    public function approve(StockRequest $stockRequest)
    {
        $this->authorizeStockRequestDepartment($stockRequest);
        $this->authorizeStockRequestProcessor($stockRequest);

        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'Stock request ini sudah diproses.');
        }

        $stockRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $recipients = InventoryMail::gaadmNotificationRecipients();
        if ($recipients === []) {
            Log::warning('Email approval Stock Request tidak dikirim: GAADM_NOTIFICATION_MAIL belum diset di .env.');
        } else {
            foreach ($recipients as $email) {
                try {
                    Mail::to($email)->send(new StockRequestApproved($stockRequest));
                } catch (\Throwable $e) {
                    Log::error('Gagal kirim email approval Stock Request', [
                        'to' => $email,
                        'stock_request_id' => $stockRequest->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return back()->with('success', 'Stock request berhasil disetujui.');
    }

    public function reject(StockRequest $stockRequest)
    {
        $this->authorizeStockRequestDepartment($stockRequest);
        $this->authorizeStockRequestProcessor($stockRequest);

        if ($stockRequest->status !== 'pending') {
            return back()->with('error', 'Stock request ini sudah diproses.');
        }

        $stockRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Stock request berhasil ditolak.');
    }

    public function export(Request $request)
    {
        $filename = 'stock_request_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new StockRequestExport($request), $filename);
    }

    public function exportPdf(StockRequest $stockRequest)
    {
        $this->authorizeStockRequestDepartment($stockRequest);

        // Samakan dengan halaman index: staf & admin Teknik hanya boleh melihat request miliknya sendiri
        $actor = auth()->user();
        if ($actor->isStaff() || ($actor->isAdmin() && $actor->isTeknik())) {
            abort_unless((int) $stockRequest->user_id === (int) $actor->id, 403, 'Anda tidak memiliki akses ke stok request ini.');
        }

        abort_unless($stockRequest->status === 'approved', 403, 'Hanya stok request yang sudah approved yang dapat diexport ke PDF.');

        $stockRequest->load(['lines.item', 'user', 'processor']);

        $isTeknik = $stockRequest->bidang === 'teknik';

        $lines = $stockRequest->lines->map(function (StockRequestLine $line) {
            $price = (int) $line->price;
            $quantity = (int) $line->quantity;

            return [
                'name' => $line->item->name ?? '-',
                'no_normalisasi' => $line->item->no_normalisasi ?? '-',
                'category' => $line->category ?: ($line->item->category ?? '-'),
                'lokasi' => $line->item->lokasi ?? '-',
                'ship_unloader' => $line->item->ship_unloader_label ?? '-',
                'quantity' => $quantity,
                'unit' => $line->item->unit ?? '',
                'price' => $price,
                'line_total' => $price * $quantity,
                'description' => $line->description ?: '-',
            ];
        })->values();

        $grandTotal = (int) $lines->sum('line_total');

        $pdf = Pdf::loadView('stock-requests.pdf', [
            'stockRequest' => $stockRequest,
            'isTeknik' => $isTeknik,
            'lines' => $lines,
            'grandTotal' => $grandTotal,
        ])->setPaper('a4', 'portrait');

        $filename = 'Stok_Request_' . $stockRequest->id . '_' . $stockRequest->created_at->format('Ymd') . '.pdf';

        return $pdf->stream($filename);
    }

    public function destroy(StockRequest $stockRequest)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Hanya superadmin yang dapat menghapus riwayat stock request.');

        $stockRequest->delete();

        return back()->with('success', 'Riwayat stock request berhasil dihapus.');
    }

    private function authorizeStockRequestDepartment(StockRequest $stockRequest): void
    {
        abort_unless(auth()->user()->canAccessBidang($stockRequest->bidang), 403, 'Anda tidak memiliki akses ke stok request bidang ini.');
    }

    private function authorizeStockRequestProcessor(StockRequest $stockRequest): void
    {
        $user = auth()->user();
        $allowed = $stockRequest->bidang === 'teknik'
            ? $user->isManager()
            : $user->isAdmin();

        abort_unless($allowed, 403, 'Anda tidak memiliki akses untuk memproses stock request ini.');
    }
}
