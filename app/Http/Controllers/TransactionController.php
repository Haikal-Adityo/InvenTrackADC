<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        // Super Admin bidang tab switching
        $saBidang = $this->superAdminBidangContext($request);
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $viewUser = $saBidang ? $this->createBidangProxy($saBidang) : auth()->user();

        $query = Transaction::with(['item', 'user', 'approver'])->visibleFor($viewUser);

        // Staff can only see their own transactions
        if (auth()->user()->isStaff()) {
            $query->where('user_id', auth()->id());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhereHas('item', fn($itemQuery) => $itemQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('component', 'like', "%{$search}%")
                        ->orWhere('no_normalisasi', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%"));
            });
        }

        $typeFilter = $request->filled('type') ? $request->type : null;
        if ($viewUser->isTeknik()) {
            $typeFilter = $typeFilter === 'out' ? 'out' : 'in';
        }

        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        $sort = $request->input('sort', 'latest') === 'oldest' ? 'asc' : 'desc';
        $transactions = $query
            ->orderBy('date', $sort)
            ->orderBy('created_at', $sort)
            ->orderBy('id', $sort)
            ->paginate(10)
            ->withQueryString();

        // Pass items for modal dropdown
        $items = Item::visibleFor($viewUser)->orderBy('name')->get();
        $transactionDetailData = $this->transactionDetailData($transactions);
        
        $yearExpr = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', date) as year" : 'YEAR(date) as year';
        $years = Transaction::visibleFor($viewUser)
            ->selectRaw($yearExpr)
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        if (!$request->has('inventrack_section') && ($request->ajax() || $request->wantsJson())) {
            return response()->json([
                'html' => view('transactions.partials.table', [
                    'transactions' => $transactions,
                    'showCreateButton' => !$viewUser->isTeknik(),
                    'saBidang' => $saBidang,
                ])->render(),
                'detailData' => $transactionDetailData,
                'total' => $transactions->total(),
                'sort' => $request->input('sort', 'latest') === 'oldest' ? 'oldest' : 'latest',
            ]);
        }

        return view('transactions.index', compact('transactions', 'items', 'transactionDetailData', 'years', 'saBidang', 'isSuperAdmin'));
    }

    public function create()
    {
        return redirect()->route('transactions.index', $this->transactionIndexParams());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:in,out',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'ship_unloader' => 'nullable|array',
            'ship_unloader.*' => 'in:1,2,3,4',
        ]);

        $item = Item::findOrFail($validated['item_id']);
        if (auth()->check()) {
            $user = auth()->user();
            abort_unless($user->isAdmin() || $user->isStaff() || $user->isSuperAdmin(), 403, 'Anda tidak memiliki akses untuk membuat transaksi.');
            abort_unless($user->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');
        } else {
            abort_unless($item->bidang === 'teknik', 403, 'Anda tidak memiliki akses ke barang bidang ini.');
        }

        $shipValidationError = $this->validateShipUnloaderForIssue($request, $item);
        if ($shipValidationError) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $shipValidationError], 422);
            }
            return back()->withInput()->with('error', $shipValidationError);
        }

        if ($validated['type'] === 'out') {
            if ($item->current_stock < $validated['quantity']) {
                $errorMsg = 'Stok tidak mencukupi. Stok saat ini: ' . $item->current_stock . ' ' . $item->unit;
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return back()->withInput()->with('error', $errorMsg);
            }
        }

        $validated['user_id'] = auth()->id();
        $validated['bidang'] = $item->bidang;
        $validated['no_normalisasi'] = $item->no_normalisasi;
        $validated['lokasi'] = $item->lokasi;
        $validated['volume'] = $item->bidang === 'teknik' ? $item->volume : null;
        $validated['ship_unloader'] = $item->bidang === 'teknik' ? $this->normalizeShipUnloader($validated['ship_unloader'] ?? []) : null;
        if ($item->bidang === 'teknik' && !$validated['ship_unloader']) {
            $errorMsg = 'Ship Unloader wajib dipilih untuk transaksi Teknik.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return back()->withInput()->with('error', $errorMsg);
        }
        $validated['status'] = $item->bidang === 'teknik' ? 'approved' : 'pending';
        $validated['approved_by'] = $item->bidang === 'teknik' ? auth()->id() : null;
        $validated['approved_at'] = $item->bidang === 'teknik' ? now() : null;
        $validated['price'] = $item->bidang === 'teknik' ? null : ($validated['price'] ?? null);

        $transaction = Transaction::create($validated);

        // Notifikasi: Umum pending → admin; Teknik langsung approved → cek stok rendah
        \App\Support\InventoryNotifier::transaksiBaru($transaction);
        if ($transaction->status === 'approved' && $transaction->type === 'out') {
            \App\Support\InventoryNotifier::cekStokRendah([$transaction->item_id]);
        }

        $successMsg = $item->bidang === 'teknik'
            ? 'Transaksi Teknik berhasil ditambahkan dan otomatis approved.'
            : 'Transaksi berhasil ditambahkan dan menunggu approval dari Admin.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        if (!auth()->check()) {
            return back()->with('success', $successMsg);
        }

        return redirect()
            ->route('transactions.index', $this->transactionIndexParams($validated['type'], $item->bidang))
            ->with('success', $successMsg);
    }

    public function edit(Transaction $transaction, Request $request)
    {
        if ($transaction->status !== 'pending' && $transaction->bidang !== 'teknik') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Hanya transaksi berstatus pending yang bisa diubah.'], 422);
            }
            return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa diubah.');
        }

        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        // AJAX request returns JSON data for modal pre-fill
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'id' => $transaction->id,
                'item_id' => $transaction->item_id,
                'date' => $transaction->date->format('Y-m-d'),
                'type' => $transaction->type,
                'quantity' => $transaction->quantity,
                'price' => $transaction->price,
                'description' => $transaction->description,
                'no_normalisasi' => $transaction->no_normalisasi,
                'lokasi' => $transaction->lokasi,
                'volume' => $transaction->volume,
                'ship_unloader' => $transaction->ship_unloader ? explode(',', $transaction->ship_unloader) : [],
                'item' => [
                    'category' => $transaction->item->category ?? '',
                    'component' => $transaction->item->component ?? '',
                    'unit' => $transaction->item->unit ?? '',
                    'current_stock' => $transaction->item->current_stock ?? 0,
                    'no_normalisasi' => $transaction->item->no_normalisasi ?? '',
                    'lokasi' => $transaction->item->lokasi ?? '',
                    'volume' => $transaction->item->volume ?? '',
                    'ship_unloader' => $transaction->item->stock_ship_unloader ?? '',
                ],
            ]);
        }

        return redirect()->route('transactions.index', $this->transactionIndexParams($transaction->type, $transaction->bidang));
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->status !== 'pending' && $transaction->bidang !== 'teknik') {
            $errorMsg = 'Hanya transaksi berstatus pending yang bisa diubah.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->route('dashboard')->with('error', $errorMsg);
        }

        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:in,out',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:500',
            'ship_unloader' => 'nullable|array',
            'ship_unloader.*' => 'in:1,2,3,4',
        ]);

        $item = Item::findOrFail($validated['item_id']);
        abort_unless(auth()->user()->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');

        $shipValidationError = $this->validateShipUnloaderForIssue($request, $item, $transaction);
        if ($shipValidationError) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $shipValidationError], 422);
            }
            return back()->withInput()->with('error', $shipValidationError);
        }

        if ($transaction->status === 'approved') {
            $stockError = $this->stockErrorForApprovedUpdate($transaction, $item, $validated['type'], (int) $validated['quantity']);
            if ($stockError) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $stockError], 422);
                }
                return back()->withInput()->with('error', $stockError);
            }
        }

        if ($validated['type'] === 'out') {
            $availableStock = $this->availableStockForUpdate($transaction, $item);
            if ($availableStock < $validated['quantity']) {
                $errorMsg = 'Stok tidak mencukupi. Stok tersedia: ' . $availableStock . ' ' . $item->unit;
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $errorMsg], 422);
                }
                return back()->withInput()->with('error', $errorMsg);
            }
        }

        $validated['bidang'] = $item->bidang;
        $validated['no_normalisasi'] = $item->no_normalisasi;
        $validated['lokasi'] = $item->lokasi;
        $validated['volume'] = $item->bidang === 'teknik' ? $item->volume : null;
        $validated['ship_unloader'] = $item->bidang === 'teknik' ? $this->normalizeShipUnloader($validated['ship_unloader'] ?? []) : null;
        if ($item->bidang === 'teknik' && !$validated['ship_unloader']) {
            $errorMsg = 'Ship Unloader wajib dipilih untuk transaksi Teknik.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return back()->withInput()->with('error', $errorMsg);
        }
        $validated['price'] = $item->bidang === 'teknik' ? null : ($validated['price'] ?? null);

        $transaction->update($validated);

        $successMsg = $transaction->bidang === 'teknik'
            ? 'Transaksi Teknik berhasil diperbarui.'
            : 'Transaksi pending berhasil diperbarui.';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('transactions.index', ['type' => $transaction->type])->with('success', $successMsg);
    }

    public function destroy(Transaction $transaction)
    {
        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $isUmum = $transaction->bidang === 'umum';

        if ($transaction->status !== 'pending' && $transaction->bidang !== 'teknik') {
            if (!($isSuperAdmin && $isUmum)) {
                return redirect()->route('dashboard')->with('error', 'Hanya transaksi berstatus pending yang bisa dihapus.');
            }
        }

        $type = $transaction->type;
        if ($transaction->status === 'approved') {
            $stockError = $this->stockErrorForApprovedDelete($transaction);
            if ($stockError) {
                return redirect()->route('transactions.index', ['type' => $type])->with('error', $stockError);
            }
        }

        $transaction->delete();

        return redirect()->route('transactions.index', ['type' => $type])->with('success', 'Transaksi berhasil dihapus.');
    }

    public function approve(Transaction $transaction)
    {
        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi pending yang bisa disetujui.');
        }

        if ($transaction->type === 'out') {
            $item = $transaction->item;
            if ($item && $item->current_stock < $transaction->quantity) {
                return back()->with('error', "Stok {$item->name} tidak mencukupi.");
            }
        }

        $transaction->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        \App\Support\InventoryNotifier::transaksiDiproses([$transaction], 'approved');
        if ($transaction->type === 'out') {
            \App\Support\InventoryNotifier::cekStokRendah([$transaction->item_id]);
        }

        return back()->with('success', 'Transaksi berhasil disetujui.');
    }

    public function reject(Transaction $transaction)
    {
        abort_unless(auth()->user()->canAccessBidang($transaction->bidang), 403, 'Anda tidak memiliki akses ke transaksi bidang ini.');

        if ($transaction->status !== 'pending') {
            return back()->with('error', 'Hanya transaksi pending yang bisa ditolak.');
        }

        $transaction->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        \App\Support\InventoryNotifier::transaksiDiproses([$transaction], 'rejected');

        return back()->with('success', 'Transaksi berhasil ditolak.');
    }

    public function getItemDetails($id)
    {
        $item = Item::findOrFail($id);
        abort_unless(auth()->user()->canAccessBidang($item->bidang), 403, 'Anda tidak memiliki akses ke barang bidang ini.');

        return response()->json([
            'category' => $item->category,
            'component' => $item->component,
            'unit' => $item->unit,
            'current_stock' => $item->current_stock,
            'no_normalisasi' => $item->no_normalisasi,
            'lokasi' => $item->lokasi,
            'volume' => $item->volume,
            'ship_unloader' => $item->stock_ship_unloader,
            'ship_unloader_label' => $item->stock_ship_unloader_label,
        ]);
    }

    private function normalizeShipUnloader(array $ships): ?string
    {
        $normalized = collect($ships)
            ->map(fn($ship) => (string) $ship)
            ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();

        return $normalized->isEmpty() ? null : $normalized->implode(',');
    }

    private function availableStockForUpdate(Transaction $transaction, Item $item): int
    {
        $availableStock = (int) $item->current_stock;

        if (
            $transaction->status === 'approved'
            && $transaction->type === 'out'
            && (int) $transaction->item_id === (int) $item->id
        ) {
            $availableStock += (int) $transaction->quantity;
        }

        return $availableStock;
    }

    private function stockErrorForApprovedUpdate(Transaction $transaction, Item $newItem, string $newType, int $newQuantity): ?string
    {
        $affected = collect([$transaction->item_id, $newItem->id])->filter()->unique();

        foreach ($affected as $itemId) {
            $item = (int) $itemId === (int) $newItem->id ? $newItem : Item::find($itemId);
            if (!$item) {
                continue;
            }

            $finalStock = (int) $item->current_stock;

            if ((int) $transaction->item_id === (int) $item->id) {
                $finalStock -= $this->transactionStockEffect($transaction->type, (int) $transaction->quantity);
            }

            if ((int) $newItem->id === (int) $item->id) {
                $finalStock += $this->transactionStockEffect($newType, $newQuantity);
            }

            if ($finalStock < 0) {
                return "Perubahan ini akan membuat stok {$item->name} menjadi negatif.";
            }
        }

        return null;
    }

    private function stockErrorForApprovedDelete(Transaction $transaction): ?string
    {
        $item = $transaction->item;
        if (!$item) {
            return null;
        }

        $finalStock = (int) $item->current_stock - $this->transactionStockEffect($transaction->type, (int) $transaction->quantity);

        return $finalStock < 0
            ? "Transaksi ini tidak bisa dihapus karena stok {$item->name} akan menjadi negatif."
            : null;
    }

    private function transactionStockEffect(string $type, int $quantity): int
    {
        return $type === 'in' ? $quantity : -$quantity;
    }

    private function transactionIndexParams(?string $type = null, ?string $bidang = null): array
    {
        $user = auth()->user();
        $type ??= request('type');

        if (($bidang === 'teknik' || $user?->isTeknik()) && in_array($type, ['in', 'out'], true)) {
            return ['type' => $type];
        }

        return [];
    }

    private function transactionDetailData($transactions): array
    {
        $data = [];

        foreach ($transactions as $txRow) {
            $data[$txRow->id] = [
                'date' => $txRow->date->format('d/m/Y'),
                'type' => $txRow->type_label,
                'no_normalisasi' => $txRow->no_normalisasi ?: ($txRow->item->no_normalisasi ?? '-'),
                'name' => $txRow->item->name ?? '-',
                'category' => $txRow->item->category ?? '-',
                'component' => $txRow->item->component ?? '-',
                'ship_unloader' => $txRow->ship_unloader_label,
                'lokasi' => $txRow->lokasi ?: ($txRow->item->lokasi ?? '-'),
                'volume' => $txRow->volume ?? '-',
                'quantity' => $txRow->quantity,
                'unit' => $txRow->item->unit ?? '-',
                'price' => $txRow->price === null ? '-' : 'Rp ' . number_format($txRow->price, 0, ',', '.'),
                'user' => $txRow->user->name ?? '-',
                'status' => $txRow->bidang === 'teknik' ? 'Approve' : ($txRow->status === 'pending' ? 'Menunggu Approval' : ucfirst($txRow->status)),
                'description' => $txRow->description ?: '-',
            ];
        }

        return $data;
    }

    private function availableShipsForUpdate(Transaction $transaction, Item $item): array
    {
        $activeShips = collect(explode(',', (string) $item->stock_ship_unloader))
            ->map(fn($s) => trim($s))
            ->filter()
            ->toArray();

        if (
            $transaction->status === 'approved'
            && $transaction->type === 'out'
            && (int) $transaction->item_id === (int) $item->id
            && $transaction->ship_unloader
        ) {
            $txShips = collect(explode(',', $transaction->ship_unloader))
                ->map(fn($s) => trim($s))
                ->filter()
                ->toArray();
            
            $activeShips = collect(array_merge($activeShips, $txShips))
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        }

        return $activeShips;
    }

    private function validateShipUnloaderForIssue(Request $request, Item $item, ?Transaction $transaction = null): ?string
    {
        if ($item->bidang !== 'teknik' || $request->input('type') !== 'out') {
            return null;
        }

        $inputShips = $request->input('ship_unloader');
        if (empty($inputShips)) {
            return null;
        }

        $availableShips = [];
        if ($transaction) {
            $availableShips = $this->availableShipsForUpdate($transaction, $item);
        } else {
            $availableShips = collect(explode(',', (string) $item->stock_ship_unloader))
                ->map(fn($s) => trim($s))
                ->filter()
                ->toArray();
        }

        foreach ($inputShips as $ship) {
            if (!in_array((string) $ship, $availableShips, true)) {
                return "Ship Unloader {$ship} tidak tersedia/tidak aktif pada barang ini.";
            }
        }

        return null;
    }
}
