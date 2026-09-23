<?php

namespace App\Http\Controllers;

use App\Mail\StuffRequestSubmitted;
use App\Models\Item;
use App\Models\StuffRequest;
use App\Models\Transaction;
use App\Support\InventoryMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class StuffRequestController extends Controller
{
    /**
     * Public landing page: stock recap + request form (no auth required).
     */
    public function publicIndex(Request $request)
    {
        $requestedBidang = $request->input('bidang', old('bidang'));
        $activeBidang = in_array($requestedBidang, ['teknik', 'umum'], true)
            ? $requestedBidang
            : 'umum';

        $query = Item::query()->where('bidang', $activeBidang);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('component', 'like', "%{$search}%")
                    ->orWhere('no_normalisasi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where($activeBidang === 'teknik' ? 'component' : 'category', $request->category);
        }

        $items = $activeBidang === 'umum'
            ? $query->orderBy('name')->paginate(20, ['*'], 'umum_page')->withQueryString()
            : $query->orderBy('name')->get();
        $categoryColumn = $activeBidang === 'teknik' ? 'component' : 'category';
        $categories = Item::where('bidang', $activeBidang)->select($categoryColumn)->whereNotNull($categoryColumn)->distinct()->pluck($categoryColumn);
        $allItems = Item::where('bidang', $activeBidang)->orderBy('name')->get(); // for the select dropdown
        $publicDashboard = null;

        if ($activeBidang === 'teknik') {
            $now = Carbon::now();
            $allTeknikItems = Item::where('bidang', 'teknik')->get();
            $yearExpr = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', date) as year" : 'YEAR(date) as year';
            $availableYears = Transaction::where('bidang', 'teknik')
                ->approved()
                ->selectRaw($yearExpr)
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year');
            $selectedYear = $availableYears->first() ?? $now->year;
            $totalItems = $allTeknikItems->count();
            $totalItemsLastMonth = Item::where('bidang', 'teknik')
                ->where('created_at', '<', $now->copy()->startOfMonth())
                ->count();
            $totalSoh = $allTeknikItems->sum(fn($item) => max(0, $item->current_stock));

            $criticalStockCount = $allTeknikItems
                ->filter(fn($item) => $item->current_stock < $item->min_stock)
                ->count();
            $lowStockCount = $allTeknikItems
                ->filter(fn($item) => $item->current_stock == $item->min_stock)
                ->count();
            $inStockCount = $allTeknikItems
                ->filter(fn($item) => $item->current_stock > $item->min_stock)
                ->count();

            $latestGR = Transaction::with(['item', 'user'])
                ->where('bidang', 'teknik')
                ->approved()
                ->masuk()
                ->latest()
                ->first();

            $latestGI = Transaction::with(['item', 'user'])
                ->where('bidang', 'teknik')
                ->approved()
                ->keluar()
                ->latest()
                ->first();

            $detailedSohTransactions = collect([$latestGR, $latestGI])
                ->filter()
                ->sortByDesc(fn($tx) => $tx->date ?? $tx->created_at)
                ->values();

            $recentTransactions = Transaction::with(['item', 'user'])
                ->where('bidang', 'teknik')
                ->approved()
                ->latest()
                ->limit(5)
                ->get();

            $recentReceipts = Transaction::with(['item', 'user'])
                ->where('bidang', 'teknik')
                ->approved()
                ->masuk()
                ->latest()
                ->paginate(10, ['*'], 'gr_page');

            $recentIssues = Transaction::with(['item', 'user'])
                ->where('bidang', 'teknik')
                ->approved()
                ->keluar()
                ->latest()
                ->paginate(10, ['*'], 'gi_page');

            $publicDashboard = [
                'totalItems' => $totalItems,
                'totalItemsMonthlyChange' => $this->monthlyPercentageChange($totalItems, $totalItemsLastMonth),
                'totalSoh' => $totalSoh,
                'masukBulanIni' => Transaction::where('bidang', 'teknik')->approved()->masuk()
                    ->whereMonth('date', $now->month)
                    ->whereYear('date', $now->year)
                    ->sum('quantity'),
                'keluarBulanIni' => Transaction::where('bidang', 'teknik')->approved()->keluar()
                    ->whereMonth('date', $now->month)
                    ->whereYear('date', $now->year)
                    ->sum('quantity'),
                'criticalStockCount' => $criticalStockCount,
                'lowStockCount' => $lowStockCount,
                'inStockCount' => $inStockCount,
                'typeSummary' => $this->publicTechnicalTypeSummary($allTeknikItems),
                'selectedMonthlyPeriod' => $request->monthly_period ?? 'thisMonth',
                'monthlyData' => $this->publicMonthlyChartData((int) $selectedYear, $request->monthly_period ?? 'thisMonth'),
                'categoryData' => $this->publicShipUnloaderStockData($allTeknikItems, (int) date('Y')),
                'availableYears' => $availableYears->isNotEmpty() ? $availableYears : collect([$now->year]),
                'selectedYear' => $selectedYear,
                'recentTransactions' => $recentTransactions,
                'detailedSohTransactions' => $detailedSohTransactions,
                'recentReceipts' => $recentReceipts,
                'recentIssues' => $recentIssues,
            ];
        }

        return view('public.stuff-request', compact('items', 'categories', 'allItems', 'activeBidang', 'publicDashboard'));
    }

    private function monthlyPercentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function publicTechnicalTypeSummary($items): array
    {
        $grouped = $items
            ->filter(fn($item) => filled($item->component))
            ->groupBy(fn($item) => $item->component);
        $totalItems = max(1, $items->count());

        return [
            'total_types' => $grouped->count(),
            'top_types' => $grouped
                ->map(fn($rows, $component) => [
                    'name' => $component,
                    'count' => $rows->count(),
                    'percentage' => (int) round(($rows->count() / $totalItems) * 100),
                ])
                ->sortByDesc('count')
                ->values()
                ->all(),
        ];
    }

    public function publicMonthlyData(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);
        $period = $request->period ?? 'thisMonth';

        return response()->json($this->publicMonthlyChartData($year, $period));
    }

    public function publicShipUnloaderData(Request $request)
    {
        $year = $request->filled('year') ? (int) $request->year : null;
        $items = Item::where('bidang', 'teknik')->get();

        return response()->json($this->publicShipUnloaderStockData($items, $year));
    }



    private function publicMonthlyChartData(int $year, string $period = 'thisMonth'): array
    {
        if (!in_array($period, ['thisMonth', '6months', 'ytd'], true)) {
            $period = 'thisMonth';
        }

        $now = Carbon::now();
        $anchorMonth = $year === (int) $now->year ? (int) $now->month : 12;
        $points = [];

        if ($period === 'thisMonth') {
            $date = Carbon::create($year, $anchorMonth, 1);
            $weeksInMonth = (int) ceil($date->daysInMonth / 7);

            for ($week = 1; $week <= $weeksInMonth; $week++) {
                $startDay = (($week - 1) * 7) + 1;
                $endDay = min($week * 7, $date->daysInMonth);
                $points[] = [
                    'label' => 'Week ' . $week,
                    'start' => $date->copy()->day($startDay)->startOfDay(),
                    'end' => $date->copy()->day($endDay)->endOfDay(),
                ];
            }
        } elseif ($period === '6months') {
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::create($year, $anchorMonth, 1)->subMonths($i);
                $points[] = [
                    'label' => $date->translatedFormat('M'),
                    'month' => (int) $date->month,
                    'year' => (int) $date->year,
                ];
            }
        } else {
            for ($month = 1; $month <= 12; $month++) {
                $date = Carbon::create($year, $month, 1);
                $points[] = [
                    'label' => $date->translatedFormat('M'),
                    'month' => $month,
                    'year' => $year,
                ];
            }
        }

        $monthlyData = [];

        foreach ($points as $point) {
            $masukQuery = Transaction::where('bidang', 'teknik')->approved()->masuk();
            $keluarQuery = Transaction::where('bidang', 'teknik')->approved()->keluar();

            if (isset($point['start'], $point['end'])) {
                $masukQuery->whereBetween('date', [$point['start'], $point['end']]);
                $keluarQuery->whereBetween('date', [$point['start'], $point['end']]);
            } else {
                $masukQuery->whereYear('date', $point['year'])->whereMonth('date', $point['month']);
                $keluarQuery->whereYear('date', $point['year'])->whereMonth('date', $point['month']);
            }

            $monthlyData[] = [
                'label' => $point['label'],
                'masuk' => (int) $masukQuery->sum('quantity'),
                'keluar' => (int) $keluarQuery->sum('quantity'),
            ];
        }

        return $monthlyData;
    }

    private function publicShipUnloaderStockData($items, ?int $year = null): array
    {
        $stockByShip = collect(['1', '2', '3', '4'])->mapWithKeys(fn($ship) => [$ship => 0])->all();
        $allStock = 0;

        foreach ($items as $item) {
            $ships = collect(explode(',', (string) $item->ship_unloader))
                ->map(fn($ship) => trim($ship))
                ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
                ->unique()
                ->values();

            if ($ships->isEmpty()) {
                continue;
            }

            $stock = $year
                ? $this->publicItemStockForYear($item->id, $year)
                : $item->current_stock;
            $stock = max(0, (int) $stock);

            if ($ships->count() === 4) {
                $allStock += $stock;
            }

            foreach ($ships as $ship) {
                $stockByShip[$ship] += $stock;
            }
        }

        $data = collect();
        $data->push([
            'category' => 'ALL',
            'stock' => $allStock,
        ]);

        foreach ($stockByShip as $ship => $stock) {
            $data->push([
                'category' => "SU-{$ship}",
                'stock' => $stock,
            ]);
        }

        return $data->values()->all();
    }

    private function publicItemStockForYear(int $itemId, int $year): int
    {
        $masuk = Transaction::where('bidang', 'teknik')->approved()->masuk()
            ->where('item_id', $itemId)
            ->whereYear('date', $year)
            ->sum('quantity');
        $keluar = Transaction::where('bidang', 'teknik')->approved()->keluar()
            ->where('item_id', $itemId)
            ->whereYear('date', $year)
            ->sum('quantity');

        return (int) ($masuk - $keluar);
    }

    /**
     * Store a stock request (no auth required).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requester_name' => 'required|string|max:255',
            'nip' => 'required|string|max:50',
            'jabatan' => 'required|string|max:100',
            'bidang' => 'required|in:teknik,umum',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'requester_name.required' => 'Nama wajib diisi.',
            'nip.required' => 'NIP wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'bidang.required' => 'Bidang wajib diisi.',
            'lines.required' => 'Tambahkan minimal satu barang.',
            'lines.min' => 'Tambahkan minimal satu barang.',
            'lines.*.item_id.required' => 'Pilih barang pada setiap baris.',
            'lines.*.item_id.exists' => 'Barang tidak ditemukan.',
            'lines.*.quantity.required' => 'Jumlah wajib diisi.',
            'lines.*.quantity.min' => 'Jumlah minimal 1.',
        ]);

        $linesInput = $validated['lines'];
        unset($validated['lines']);

        $mergedByItem = [];
        foreach ($linesInput as $line) {
            $id = (int) $line['item_id'];
            $mergedByItem[$id] = ($mergedByItem[$id] ?? 0) + (int) $line['quantity'];
        }

        $itemsById = Item::whereIn('id', array_keys($mergedByItem))
            ->where('bidang', $validated['bidang'])
            ->get()
            ->keyBy('id');

        if ($itemsById->count() !== count($mergedByItem)) {
            throw ValidationException::withMessages([
                'lines' => 'Barang yang dipilih tidak sesuai dengan bidang permintaan.',
            ]);
        }

        $stockErrors = [];

        foreach ($mergedByItem as $itemId => $quantity) {
            $item = $itemsById->get($itemId);
            if ($item && $quantity > $item->current_stock) {
                $stockErrors[] = "Jumlah {$item->name} melebihi stok tersedia ({$item->current_stock} {$item->unit}).";
            }
        }

        if ($stockErrors !== []) {
            throw ValidationException::withMessages([
                'lines' => $stockErrors,
            ]);
        }

        $stuffRequest = DB::transaction(function () use ($validated, $mergedByItem) {
            $validated['status'] = 'pending';
            $stuffRequest = StuffRequest::create($validated);
            foreach ($mergedByItem as $itemId => $quantity) {
                $stuffRequest->lines()->create([
                    'item_id' => $itemId,
                    'quantity' => $quantity,
                ]);
            }

            return $stuffRequest;
        });

        $recipients = InventoryMail::gaadmNotificationRecipients();
        if ($recipients === []) {
            Log::warning('Email Permintaan Barang tidak dikirim: GAADM_NOTIFICATION_MAIL belum diset di .env.');
        } else {
            foreach ($recipients as $email) {
                try {
                    Mail::to($email)->send(new StuffRequestSubmitted($stuffRequest));
                } catch (\Throwable $e) {
                    Log::error('Gagal kirim email Permintaan Barang', [
                        'to' => $email,
                        'stuff_request_id' => $stuffRequest->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->route('public.stuff-request', ['bidang' => $validated['bidang']])
            ->with('success', 'Permintaan barang berhasil dikirim! Permintaan Anda akan ditinjau oleh Admin.');
    }

    /**
     * Admin/staff: list stock requests with date filter.
     */
    public function adminIndex(Request $request)
    {
        $query = StuffRequest::with(['lines.item', 'processor', 'completer'])
            ->visibleFor(auth()->user())
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('requester_name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhereHas('lines.item', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%")
                            ->orWhere('component', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month);
        }

        $requests = $query->paginate(15)->withQueryString();

        $pendingCount = StuffRequest::visibleFor(auth()->user())->pending()->count();

        $yearExpr = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', created_at) as year" : 'YEAR(created_at) as year';
        $years = StuffRequest::visibleFor(auth()->user())
            ->selectRaw($yearExpr)
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();

        return view('stuff-requests.index', compact('requests', 'pendingCount', 'years'));
    }

    /**
     * Autocomplete endpoint: returns stuff requests matching the keyword from the database.
     * Used by the search suggestion box on the stuff-requests index page.
     */
    public function autocomplete(Request $request)
    {
        $keyword = trim($request->input('q', ''));

        if (!$keyword || mb_strlen($keyword) < 1) {
            return response()->json([]);
        }

        $results = StuffRequest::with('lines.item')
            ->visibleFor(auth()->user())
            ->where(function ($q) use ($keyword) {
                $q->where('requester_name', 'like', "%{$keyword}%")
                  ->orWhere('nip', 'like', "%{$keyword}%")
                  ->orWhere('jabatan', 'like', "%{$keyword}%")
                  ->orWhere('bidang', 'like', "%{$keyword}%")
                  ->orWhereHas('lines.item', function ($q2) use ($keyword) {
                      $q2->where('name', 'like', "%{$keyword}%");
                  });
            })
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn($req) => [
                'id'       => $req->id,
                'name'     => $req->requester_name,
                'nip'      => $req->nip,
                'jabatan'  => $req->jabatan,
                'bidang'   => $req->bidang,
                'items'    => $req->lines->pluck('item.name')->filter()->join(', '),
            ]);

        return response()->json($results);
    }

    /**
     * Approve a stock request.
     */
    public function approve(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);
        abort_if($stuffRequest->bidang === 'teknik', 403, 'Permintaan barang Teknik langsung diselesaikan tanpa tahap approve/reject.');

        if ($stuffRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $stuffRequest->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Permintaan barang dari {$stuffRequest->requester_name} telah disetujui.");
    }

    /**
     * Reject a stock request.
     */
    public function reject(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);
        abort_if($stuffRequest->bidang === 'teknik', 403, 'Stuff Request Teknik langsung diselesaikan tanpa tahap approve/reject.');

        if ($stuffRequest->status !== 'pending') {
            return back()->with('error', 'Request ini sudah diproses.');
        }

        $stuffRequest->update([
            'status' => 'rejected',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "Permintaan barang dari {$stuffRequest->requester_name} telah ditolak.");
    }

    /**
     * Mark an approved stock request as completed.
     */
    public function complete(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);

        $allowedStatus = $stuffRequest->bidang === 'teknik' ? 'pending' : 'approved';

        if ($stuffRequest->status !== $allowedStatus) {
            return back()->with('error', $stuffRequest->bidang === 'teknik'
                ? 'Hanya request pending Bidang Teknik yang bisa langsung diselesaikan.'
                : 'Hanya request yang sudah disetujui yang bisa diselesaikan.');
        }

        $stuffRequest->load('lines.item');

        try {
            DB::transaction(function () use ($stuffRequest) {
                foreach ($stuffRequest->lines as $line) {
                    if ($line->item && $line->item->current_stock < $line->quantity) {
                        throw new \RuntimeException("Stok {$line->item->name} tidak mencukupi.");
                    }
                }

                foreach ($stuffRequest->lines as $line) {
                    Transaction::create([
                        'item_id' => $line->item_id,
                        'user_id' => auth()->id(),
                        'bidang' => $stuffRequest->bidang,
                        'no_normalisasi' => $line->item->no_normalisasi,
                        'lokasi' => $line->item->lokasi,
                        'volume' => (int) $line->quantity,
                        'ship_unloader' => $line->item->ship_unloader,
                        'date' => now()->toDateString(),
                        'type' => 'out',
                        'quantity' => (int) $line->quantity,
                        'price' => 0,
                        'description' => "Otomatis dibuat dari Permintaan Barang ID: {$stuffRequest->id}",
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                }

                $updatePayload = [
                    'status' => 'completed',
                    'completed_by' => auth()->id(),
                    'completed_at' => now(),
                ];

                if ($stuffRequest->bidang === 'teknik') {
                    $updatePayload['processed_by'] = auth()->id();
                    $updatePayload['processed_at'] = now();
                }

                $stuffRequest->update($updatePayload);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Permintaan barang dari {$stuffRequest->requester_name} telah diselesaikan dan transaksi OUT approved otomatis dibuat.");
    }

    /**
     * Cancel an approved stock request, or a pending Teknik request by admin.
     */
    public function cancel(StuffRequest $stuffRequest)
    {
        $this->authorizeRequestDepartment($stuffRequest);

        $user = auth()->user();
        $canCancelPendingTeknik = $stuffRequest->bidang === 'teknik'
            && $stuffRequest->status === 'pending'
            && ($user->isAdmin() || $user->isManager())
            && $user->isTeknik();

        if ($stuffRequest->status !== 'approved' && !$canCancelPendingTeknik) {
            return back()->with('error', 'Hanya request yang sudah disetujui, atau request pending Bidang Teknik oleh admin Teknik, yang bisa dibatalkan.');
        }

        $stuffRequest->update([
            'status' => 'cancel',
            'completed_by' => auth()->id(),
            'completed_at' => now(),
        ]);

        return back()->with('success', "Permintaan barang dari {$stuffRequest->requester_name} telah dibatalkan.");
    }

    public function destroy(StuffRequest $stuffRequest)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Hanya superadmin yang dapat menghapus riwayat permintaan barang.');

        $stuffRequest->delete();

        return back()->with('success', 'Riwayat permintaan barang berhasil dihapus.');
    }

    private function authorizeRequestDepartment(StuffRequest $stuffRequest): void
    {
        abort_unless(auth()->user()->canAccessBidang($stuffRequest->bidang), 403, 'Anda tidak memiliki akses ke permintaan bidang ini.');
    }
}
