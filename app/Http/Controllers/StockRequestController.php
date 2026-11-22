<?php

namespace App\Http\Controllers;

use App\Exports\StockRequestExport;
use App\Mail\StockRequestApproved;
use App\Mail\StockRequestNeedsApproval;
use App\Models\AppSetting;
use App\Models\DocumentPrint;
use App\Mail\StockRequestSubmitted;
use App\Models\Item;
use App\Models\StockRequest;
use App\Models\StockRequestLine;
use App\Support\InventoryMail;
use App\Support\PdfQr;
use App\Support\StockRequestPricing;
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
        $query = $this->filteredQuery($request);

        $stockRequests = $query->paginate(15)->withQueryString();
        $categories = Item::visibleFor(auth()->user())->select('category')->distinct()->orderBy('category')->pluck('category');
        $pendingCount = $this->actionableQuery()->count();

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

        $isTeknikActor = auth()->user()->isTeknik();

        $validated = $request->validate([
            'need_month' => [$isTeknikActor ? 'nullable' : 'required', 'date_format:Y-m'],
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|distinct|exists:items,id',
            'lines.*.price' => 'required|integer|min:0',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.description' => 'nullable|string|max:500',
        ], [
            'lines.required' => 'Tambahkan minimal satu barang.',
            'lines.*.item_id.distinct' => 'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
            'lines.*.quantity.min' => 'Jumlah request minimal 1.',
            'need_month.required' => 'Pilih bulan Periode Kebutuhan.',
            'need_month.date_format' => 'Format Periode Kebutuhan tidak valid.',
        ]);

        // Periode Kebutuhan global (1 bulan) → header: tgl 1 s/d akhir bulan; tiap baris ikut disimpan
        $needMonth = filled($validated['need_month'] ?? null)
            ? \Carbon\Carbon::createFromFormat('Y-m-d', $validated['need_month'].'-01')->startOfMonth()
            : null;

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
                    ->whereIn('status', ['pending', 'verified']);
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

        $pricing = StockRequestPricing::forItems($requestedItemIds);

        $createdStockRequests = DB::transaction(function () use ($validated, $pricing, $needMonth) {
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
                    'need_start' => $needMonth?->toDateString(),
                    'need_end' => $needMonth?->copy()->endOfMonth()->toDateString(),
                    'status' => 'pending',
                ]);
                $stockRequest->created_at = $timestamp;
                $stockRequest->updated_at = $timestamp;
                $stockRequest->save();

                foreach ($rows as $row) {
                    $line = $row['line'];
                    $item = $row['item'];

                    $stockRequest->lines()->create([
                        'reference_stock_request_id' => $pricing[(int) $line['item_id']]['reference']['id'] ?? null,
                        'item_id' => $line['item_id'],
                        'category' => $item?->category,
                        'price' => (int) $line['price'],
                        'quantity' => (int) $line['quantity'],
                        'need_month' => $needMonth?->toDateString(),
                        'description' => $line['description'] ?? null,
                    ]);
                }

                $createdStockRequests->push($stockRequest);
            }

            return $createdStockRequests;
        });

        \App\Support\InventoryNotifier::stokRequestBaru($createdStockRequests);

        // Email ke approver per bidang (Umum → admin umum, Teknik → manajer teknik)
        foreach ($createdStockRequests->groupBy('bidang') as $bidang => $group) {
            InventoryMail::sendTo(
                InventoryMail::approverRecipients($bidang),
                new StockRequestSubmitted($group->values()),
                'Stok Request baru',
                ['stock_request_ids' => $group->pluck('id')->all()]
            );
        }

        return redirect()->route('stock-requests.index')
            ->with('success', "{$createdStockRequests->count()} Permintaan Stok Barang berhasil dibuat.");
    }

    /** Tahap 1 (Umum): admin bidang memverifikasi → diteruskan ke manajer. */
    public function verify(StockRequest $stockRequest)
    {
        $this->authorizeStockRequestDepartment($stockRequest);
        abort_unless($stockRequest->usesTwoStepApproval() && auth()->user()->isAdmin(), 403, 'Hanya GA Admin yang dapat memverifikasi stok request ini.');

        if (! $this->transition($stockRequest, ['pending'], [
            'status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ])) {
            return back()->with('error', 'Stok request ini sudah diproses.');
        }

        \App\Support\InventoryNotifier::stokRequestTerverifikasi($stockRequest);

        InventoryMail::sendTo(
            InventoryMail::managerRecipients($stockRequest->bidang),
            new StockRequestNeedsApproval($stockRequest),
            'Stok Request menunggu persetujuan',
            ['stock_request_id' => $stockRequest->id]
        );

        return back()->with('success', "Stok request {$stockRequest->document_number} berhasil diverifikasi dan diteruskan ke Leader untuk disetujui.");
    }

    public function approve(StockRequest $stockRequest)
    {
        $this->authorizeStockRequestDepartment($stockRequest);
        $this->authorizeStockRequestProcessor($stockRequest);

        if ($stockRequest->usesTwoStepApproval() && $stockRequest->status === 'pending') {
            return back()->with('error', 'Stok request ini harus diverifikasi GA Admin terlebih dahulu.');
        }

        if (! $this->transition($stockRequest, [$this->approvableStatus($stockRequest)], [
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ])) {
            return back()->with('error', 'Stok request ini sudah diproses.');
        }

        \App\Support\InventoryNotifier::stokRequestDiproses($stockRequest);

        // Email hasil approval ke staf pembuat request
        $owner = $stockRequest->user;
        InventoryMail::sendTo(
            $owner && $owner->email ? [strtolower(trim($owner->email))] : [],
            new StockRequestApproved($stockRequest),
            'approval Stok Request',
            ['stock_request_id' => $stockRequest->id]
        );

        return back()->with('success', 'Stok request berhasil disetujui.');
    }

    public function reject(StockRequest $stockRequest)
    {
        $this->authorizeStockRequestDepartment($stockRequest);
        $user = auth()->user();

        // Umum: admin menolak saat verifikasi (pending), manajer menolak saat persetujuan (verified)
        $allowed = $stockRequest->usesTwoStepApproval()
            ? (($stockRequest->status === 'pending' && $user->isAdmin()) || ($stockRequest->status === 'verified' && $user->isManager()))
            : ($stockRequest->status === 'pending' && $user->isManager());

        if (! in_array($stockRequest->status, ['pending', 'verified'], true)) {
            return back()->with('error', 'Stok request ini sudah diproses.');
        }
        abort_unless($allowed, 403, 'Anda tidak memiliki akses untuk menolak stok request pada tahap ini.');

        if (! $this->transition($stockRequest, [$stockRequest->status], [
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ])) {
            return back()->with('error', 'Stok request ini sudah diproses.');
        }

        \App\Support\InventoryNotifier::stokRequestDiproses($stockRequest);

        return back()->with('success', 'Stok request berhasil ditolak.');
    }

    public function export(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,verified,approved,rejected']);

        $suffix = $request->filled('status') ? '_' . $request->status : '';
        $filename = 'stock_request' . $suffix . '_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new StockRequestExport($this->filteredQuery($request)), $filename);
    }

    public function exportAllPdf(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:pending,verified,approved,rejected',
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $stockRequests = $this->filteredQuery($request)->get();
        $user = auth()->user();
        $isTeknik = $user->bidang === 'teknik';

        $rows = $stockRequests->flatMap(function (StockRequest $stockRequest) {
            return $stockRequest->lines->map(function (StockRequestLine $line) use ($stockRequest) {
                $price = (int) $line->price;
                $quantity = (int) $line->quantity;

                return [
                    'request_id' => $stockRequest->id,
                    'request_number' => $stockRequest->document_number,
                    'date' => $stockRequest->created_at->format('d/m/Y'),
                    'requester' => $stockRequest->user->name ?? '-',
                    'name' => $line->item->name ?? '-',
                    'no_normalisasi' => $line->item->no_normalisasi ?? '-',
                    'ship_unloader' => $line->item->ship_unloader_label ?? '-',
                    'category' => $line->category ?: ($line->item->category ?? 'Tanpa Kategori'),
                    'quantity' => $quantity,
                    'unit' => $line->item->unit ?? '',
                    'price' => $price,
                    'line_total' => $price * $quantity,
                    'status' => $stockRequest->status,
                    'status_label' => $stockRequest->status_label,
                    'processor' => $stockRequest->processor->name ?? '-',
                ];
            });
        })->values();

        // Tabel per kategori (kolom kategori tidak ditampilkan), subtotal per kategori + grand total
        $groups = $rows->groupBy('category')->sortKeys()->map(fn ($items, $category) => [
            'category' => $category,
            'rows' => $items->values(),
            'subtotal' => (int) $items->sum('line_total'),
        ])->values();

        // Setiap klik Export = PDF baru dengan nomor urut global (cetakan ke-N). File disimpan,
        // lalu user diarahkan ke link PDF-nya; membuka ulang link tsb tidak menambah hitungan.
        $filters = array_filter($request->only(['year', 'month', 'status', 'category']), 'filled');
        $grandTotal = (int) $rows->sum('line_total');
        $bidangLabel = $user->isSuperAdmin() ? 'SEMUA BIDANG' : 'BIDANG ' . strtoupper($user->bidang ?? '-');

        $doc = DB::transaction(function () use ($user, $filters, $rows, $grandTotal, $groups, $isTeknik, $bidangLabel) {
            $doc = DocumentPrint::create([
                'bidang' => $user->isSuperAdmin() ? null : $user->bidang,
                'filters' => $filters,
                'row_count' => $rows->count(),
                'grand_total' => $grandTotal,
                'printed_by' => $user->id,
            ]);
            $doc->document_number = DocumentPrint::numberFor($doc->id, $doc->created_at);

            $logUrl = route('items.activity-log', ['dokumen' => $doc->document_number]);

            $pdf = Pdf::loadView('stock-requests.pdf-list', [
                'groups' => $groups,
                'grandTotal' => $grandTotal,
                'isTeknik' => $isTeknik,
                'bidang' => $bidangLabel,
                'docNumber' => $doc->document_number,
                'docDate' => $doc->created_at->timezone(config('app.timezone')),
                'printCount' => $doc->id,
                'printedBy' => $user->name,
                'qr' => PdfQr::dataUri($logUrl),
                'logUrl' => $logUrl,
            ])->setPaper('a4', 'landscape');

            $doc->file_path = 'rekap-persediaan/' . $doc->document_number . '.pdf';
            \Illuminate\Support\Facades\Storage::disk('local')->put($doc->file_path, $pdf->output());
            $doc->save();

            return $doc;
        });

        \App\Models\ActivityLog::record(
            'document_printed',
            'Cetak PDF Rekap Persediaan Barang (cetakan ke-' . $doc->id . ', ' . $doc->row_count . ' baris barang)',
            null,
            $doc->document_number,
            ['filters' => $filters, 'print_count' => $doc->id]
        );

        return redirect()->route('stock-requests.recap.show', $doc);
    }

    /** Buka ulang PDF Rekap yang sudah dibuat (tidak menambah cetakan). */
    public function showRecap(DocumentPrint $documentPrint)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $documentPrint->bidang === null || $user->canAccessBidang($documentPrint->bidang), 403);

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        abort_unless($documentPrint->file_path && $disk->exists($documentPrint->file_path), 404, 'File PDF rekap tidak ditemukan.');

        return response()->file($disk->path($documentPrint->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Rekap_Persediaan_Barang_' . $documentPrint->document_number . '.pdf"',
        ]);
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

        $stockRequest->load(['lines.item', 'user', 'processor', 'verifier']);

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
                'need_month' => $line->need_month_label ?: '-',
                'description' => $line->description ?: '-',
            ];
        })->values();

        $grandTotal = (int) $lines->sum('line_total');

        $pdf = Pdf::loadView('stock-requests.pdf', [
            'stockRequest' => $stockRequest,
            'isTeknik' => $isTeknik,
            'lines' => $lines,
            'grandTotal' => $grandTotal,
            'settings' => AppSetting::pdfStockRequest(),
        ])->setPaper('a4', 'portrait');

        $filename = 'Permintaan_Barang_' . $stockRequest->document_number . '.pdf';

        return $pdf->stream($filename);
    }

    /** Menu superadmin: edit teks PDF Form Permintaan (Rincian Stok Request). */
    public function pdfSettings()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('stock-requests.pdf-settings', [
            'settings' => AppSetting::pdfStockRequest(),
            'defaults' => collect(AppSetting::PDF_STOCK_REQUEST_DEFAULTS)->mapWithKeys(fn ($v, $k) => [str_replace('pdf_sr.', '', $k) => $v])->all(),
            'sample' => StockRequest::where('status', 'approved')->latest()->first(),
        ]);
    }

    public function updatePdfSettings(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $data = $request->validate([
            'title' => 'required|string|max:100',
            'subtitle' => 'nullable|string|max:100',
            'verify_caption' => 'required|string|max:50',
            'verify_role' => 'required|string|max:60',
            'approve_caption' => 'required|string|max:50',
            'approve_role' => 'required|string|max:60',
            'footer' => 'nullable|string|max:255',
        ]);

        AppSetting::putMany(collect($data)->mapWithKeys(fn ($v, $k) => ['pdf_sr.'.$k => $v])->all());

        return back()->with('success', 'Pengaturan PDF Form Permintaan berhasil disimpan.');
    }

    public function destroy(StockRequest $stockRequest)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Hanya superadmin yang dapat menghapus riwayat stock request.');

        $stockRequest->delete();

        return back()->with('success', 'Riwayat stock request berhasil dihapus.');
    }

    /**
     * Query stok request sesuai hak akses user + filter (tahun, bulan, status, kategori).
     * Dipakai bersama oleh halaman index, export Excel, dan export PDF supaya datanya selalu konsisten.
     */
    private function filteredQuery(Request $request)
    {
        $user = auth()->user();

        return StockRequest::with(['lines.item', 'lines.referenceRequest', 'user', 'processor', 'verifier'])
            ->visibleFor($user)
            ->when($user->isStaff() || ($user->isAdmin() && $user->isTeknik()), fn($query) => $query->where('user_id', $user->id))
            ->when($request->filled('year'), fn($query) => $query->whereYear('created_at', $request->year))
            ->when($request->filled('month'), fn($query) => $query->whereMonth('created_at', $request->month))
            ->when($request->filled('status'), fn($query) => $query->where('status', $request->status))
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('category', $request->category)
                        ->orWhereHas('lines.item', fn($lineQuery) => $lineQuery->where('category', $request->category));
                });
            })
            ->latest();
    }

    private function authorizeStockRequestDepartment(StockRequest $stockRequest): void
    {
        abort_unless(auth()->user()->canAccessBidang($stockRequest->bidang), 403, 'Anda tidak memiliki akses ke stok request bidang ini.');
    }

    /** Persetujuan akhir: Umum & Teknik sama-sama oleh manajer bidang. */
    private function authorizeStockRequestProcessor(StockRequest $stockRequest): void
    {
        abort_unless(auth()->user()->isManager(), 403, 'Hanya Leader (manajer) yang dapat menyetujui stok request ini.');
    }

    /**
     * Ubah status secara atomik (hanya jika status saat ini masih salah satu $from).
     * Mencegah klik ganda / dua approver bersamaan memproses (dan mengirim notifikasi) dua kali.
     */
    private function transition(StockRequest $stockRequest, array $from, array $attributes): bool
    {
        $affected = StockRequest::whereKey($stockRequest->id)
            ->whereIn('status', $from)
            ->update($attributes + ['updated_at' => now()]);

        if ($affected === 0) {
            return false;
        }

        $stockRequest->refresh();

        return true;
    }

    private function approvableStatus(StockRequest $stockRequest): string
    {
        return $stockRequest->usesTwoStepApproval() ? 'verified' : 'pending';
    }

    /** Stok request yang menunggu aksi user login (untuk badge "x pending"). */
    private function actionableQuery()
    {
        $user = auth()->user();
        $query = StockRequest::visibleFor($user);

        if ($user->isAdmin() && ! $user->isTeknik()) {
            return $query->where('status', 'pending');
        }
        if ($user->isManager()) {
            return $query->where('status', $user->isTeknik() ? 'pending' : 'verified');
        }

        return $query->open()->when($user->isStaff(), fn ($q) => $q->where('user_id', $user->id));
    }
}
