<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\StockRequest;
use App\Models\Transaction;
use App\Mail\LowStockAlert;
use App\Support\InventoryMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Staff should not access dashboard
        if ($user->isStaff()) {
            return redirect()->route('transactions.index');
        }

        // Super Admin bidang tab switching
        $saBidang = $this->superAdminBidangContext($request);
        $isSuperAdmin = $user->isSuperAdmin();
        if ($saBidang) {
            $user = $this->createBidangProxy($saBidang);
        }

        $now = Carbon::now();
        $yearExpr = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', date) as year" : 'YEAR(date) as year';

        // ========================
        // 🔥 YEAR FILTER
        // ========================
        $availableYears = Transaction::visibleFor($user)
            ->approved()
            ->selectRaw($yearExpr)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $selectedYear = $request->year ?? $availableYears->first() ?? $now->year;
        $selectedMonthlyPeriod = $request->monthly_period ?? ($user->isTeknik() ? 'thisMonth' : 'ytd');

        // Stats cards
        $totalItems = Item::visibleFor($user)->count();
        $totalItemsLastMonth = Item::visibleFor($user)
            ->where('created_at', '<', $now->copy()->startOfMonth())
            ->count();
        $totalItemsMonthlyChange = $this->monthlyPercentageChange($totalItems, $totalItemsLastMonth);
        $masukBulanIni = Transaction::visibleFor($user)->approved()->masuk()
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('quantity');
        $keluarBulanIni = Transaction::visibleFor($user)->approved()->keluar()
            ->whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->sum('quantity');
        // Hitung "Menunggu Approval" sesuai kewenangan approval role yang sedang login,
        // bukan sekadar total pending di sistem (supaya tidak menampilkan antrian approval
        // milik role lain, mis. Manajer bidang Umum yang tidak berwenang approve apa pun).
        $pendingCount = match (true) {
            $user->isManager() && $user->isTeknik() => StockRequest::visibleFor($user)->pending()->count(),
            $user->isManager() && !$user->isTeknik() => 0,
            default => Transaction::visibleFor($user)->pending()->count(),
        };

        // Low stock items
        $allItems = Item::visibleFor($user)->get();
        $lowStockItems = $allItems->filter(fn($item) => $item->is_low_stock && $item->current_stock >= 0);
        $criticalStockCount = $user->isTeknik()
            ? $allItems->filter(fn($item) => $item->current_stock < $item->min_stock)->count()
            : 0;


        // 📊 MONTHLY CHART (FIXED 12 BULAN)
        // ========================
        $monthlyData = $this->monthlyChartData($user, (int) $selectedYear, $selectedMonthlyPeriod);

        // Available years for category filter
        $availableYears = Transaction::visibleFor($user)
            ->approved()
            ->selectRaw($yearExpr)
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        // Default range
        $startYear = $request->start_year ?? $availableYears->first() ?? $now->year - 4;
        $endYear = $request->end_year ?? $availableYears->last() ?? $now->year;

        // VALIDASI RANGE (penting)
        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        // ========================
        // 📊 YEARLY DATA (DYNAMIC RANGE)
        // ========================
        $yearlyData = [];

        for ($year = $startYear; $year <= $endYear; $year++) {

            $masuk = Transaction::visibleFor($user)->approved()->masuk()
                ->whereYear('date', $year)
                ->sum('quantity');

            $keluar = Transaction::visibleFor($user)->approved()->keluar()
                ->whereYear('date', $year)
                ->sum('quantity');

            $yearlyData[] = [
                'label' => (string) $year,
                'masuk' => (int) $masuk,
                'keluar' => (int) $keluar,
            ];
        }

        $categoryData = $user->isTeknik()
            ? $this->shipUnloaderStockData($user, (int) date('Y'))
            : $this->categoryStockData($user);
        $technicalTypeSummary = $user->isTeknik()
            ? $this->technicalTypeSummary($allItems)
            : null;

        // Top 5 items most keluar
        $topKeluar = Transaction::visibleFor($user)->approved()->keluar()
            ->selectRaw('item_id, SUM(quantity) as total')
            ->groupBy('item_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('item')
            ->get();

        // Recent transactions
        $recentTransactions = Transaction::with(['item', 'user'])
            ->visibleFor($user)
            ->latest()
            ->limit(5)
            ->get();

        // Detailed SOH transactions (latest approved GR and latest approved GI)
        $latestGR = Transaction::with(['item', 'user'])
            ->visibleFor($user)
            ->approved()
            ->masuk()
            ->latest()
            ->first();

        $latestGI = Transaction::with(['item', 'user'])
            ->visibleFor($user)
            ->approved()
            ->keluar()
            ->latest()
            ->first();

        $detailedSohTransactions = collect([$latestGR, $latestGI])
            ->filter()
            ->sortByDesc(fn($tx) => $tx->date ?? $tx->created_at)
            ->values();

        // Pending transactions grouped by date (for admin daily approval)
        $pendingByDate = [];
        if ($user->isAdmin()) {
            $pendingByDate = Transaction::visibleFor($user)->pending()
                ->with(['item', 'user'])
                ->orderBy('date', 'desc')
                ->get()
                ->groupBy(function ($tx) {
                    return $tx->date->format('Y-m-d');
                });
        }

        // Items for transaction edit modal dropdown
        $items = Item::visibleFor($user)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'totalItems',
            'totalItemsMonthlyChange',
            'masukBulanIni',
            'keluarBulanIni',
            'pendingCount',
            'lowStockItems',
            'criticalStockCount',
            'monthlyData',
            'selectedMonthlyPeriod',
            'yearlyData',
            'selectedYear',
            'categoryData',
            'technicalTypeSummary',
            'availableYears',
            'startYear',
            'endYear',
            'topKeluar',
            'recentTransactions',
            'detailedSohTransactions',
            'pendingByDate',
            'items',
            'saBidang',
            'isSuperAdmin'
        ));
    }

    private function monthlyPercentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function monthlyData(Request $request)
    {
        $user = auth()->user();
        $saBidang = $this->superAdminBidangContext($request);
        if ($saBidang) {
            $user = $this->createBidangProxy($saBidang);
        }

        $year = (int) ($request->year ?? now()->year);
        $period = $request->period ?? ($user->isTeknik() ? 'thisMonth' : 'ytd');

        return response()->json($this->monthlyChartData($user, $year, $period));
    }

    private function monthlyChartData($user, int $year, string $period = 'ytd'): array
    {
        if (!in_array($period, ['thisMonth', '6months', 'ytd'], true)) {
            $period = 'ytd';
        }

        $now = Carbon::now();
        $anchorMonth = $year === (int) $now->year ? (int) $now->month : 12;
        $points = [];

        if ($period === 'thisMonth') {
            $date = Carbon::create($year, $year === (int) $now->year ? (int) $now->month : 12, 1);
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
            $masukQuery = Transaction::visibleFor($user)->approved()->masuk();
            $keluarQuery = Transaction::visibleFor($user)->approved()->keluar();

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

    /**
     * Approve all pending transactions for a specific date
     */
    public function approveByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = $request->date;

        $transactions = Transaction::visibleFor(auth()->user())->pending()->whereDate('date', $date)->get();

        if ($transactions->isEmpty()) {
            return back()->with('error', 'Tidak ada transaksi pending untuk tanggal ini.');
        }

        // Check stock for all keluar transactions first
        foreach ($transactions as $tx) {
            if ($tx->type === 'out') {
                $item = $tx->item;
                if ($item && $item->current_stock < $tx->quantity) {
                    return back()->with('error', "Stok {$item->name} tidak mencukupi ({$item->current_stock} {$item->unit}). Tidak bisa approve.");
                }
            }
        }

        // Approve all
        $count = 0;
        foreach ($transactions as $tx) {
            $tx->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $count++;
        }

        // Check low stock alerts after approval
        $itemIds = $transactions->pluck('item_id')->unique();
        foreach ($itemIds as $itemId) {
            $item = Item::find($itemId);
            if ($item && $item->is_low_stock) {
                $recipients = InventoryMail::adminNotificationRecipients($item->bidang);
                if ($recipients === []) {
                    Log::warning('Email stok rendah tidak dikirim: tidak ada alamat admin (periksa user role=admin dan email, atau set INVENTORY_ALERT_MAIL di .env).');
                } else {
                    foreach ($recipients as $email) {
                        try {
                            Mail::to($email)->send(new LowStockAlert($item));
                        } catch (\Throwable $e) {
                            Log::error('Gagal kirim email stok rendah', [
                                'to' => $email,
                                'item_id' => $item->id,
                                'message' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        // Sync to Google Sheets
        try {
            app(\App\Http\Controllers\GoogleSheetController::class)->syncAllApprovedToSheet();
        } catch (\Exception $e) {
            Log::error('Google Sheets sync after approval failed: ' . $e->getMessage());
        }

        return back()->with('success', "{$count} transaksi tanggal " . Carbon::parse($date)->format('d/m/Y') . " berhasil di-approve.");
    }

    /**
     * Reject all pending transactions for a specific date
     */
    public function rejectByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = $request->date;

        $count = Transaction::visibleFor(auth()->user())->pending()
            ->whereDate('date', $date)
            ->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

        if ($count === 0) {
            return back()->with('error', 'Tidak ada transaksi pending untuk tanggal ini.');
        }

        return back()->with('success', "{$count} transaksi tanggal " . Carbon::parse($date)->format('d/m/Y') . " berhasil di-reject.");
    }

    /**
     * AJAX: Search items for autocomplete
     */
    public function searchItems(Request $request)
    {
        $user = auth()->user();
        $saBidang = $this->superAdminBidangContext($request);
        if ($saBidang) {
            $user = $this->createBidangProxy($saBidang);
        }

        $q = $request->get('q', '');
        $items = Item::visibleFor($user)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('component', 'like', "%{$q}%")
                    ->orWhere('no_normalisasi', 'like', "%{$q}%")
                    ->orWhere('lokasi', 'like', "%{$q}%");
            })
            ->select('id', 'name', 'category', 'component', 'no_normalisasi')
            ->limit(10)
            ->get();
        return response()->json($items);
    }

    /**
     * AJAX: Get monthly + yearly chart data, optionally filtered by item_id
     */
    public function chartData(Request $request)
    {
        $user = auth()->user();
        $saBidang = $this->superAdminBidangContext($request);
        if ($saBidang) {
            $user = $this->createBidangProxy($saBidang);
        }

        $itemId = $request->get('item_id');
        $now = Carbon::now();

        // Monthly (last 12 months)
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthLabel = $month->translatedFormat('M Y');

            $masukQuery = Transaction::visibleFor($user)->approved()->masuk()
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year);
            $keluarQuery = Transaction::visibleFor($user)->approved()->keluar()
                ->whereMonth('date', $month->month)
                ->whereYear('date', $month->year);

            if ($itemId) {
                $masukQuery->where('item_id', $itemId);
                $keluarQuery->where('item_id', $itemId);
            }

            $monthlyData[] = [
                'label' => $monthLabel,
                'masuk' => (int) $masukQuery->sum('quantity'),
                'keluar' => (int) $keluarQuery->sum('quantity'),
            ];
        }

        // Yearly (last 5 years)
        $yearlyData = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = $now->copy()->subYears($i)->year;

            $masukQuery = Transaction::visibleFor($user)->approved()->masuk()->whereYear('date', $year);
            $keluarQuery = Transaction::visibleFor($user)->approved()->keluar()->whereYear('date', $year);

            if ($itemId) {
                $masukQuery->where('item_id', $itemId);
                $keluarQuery->where('item_id', $itemId);
            }

            $yearlyData[] = [
                'label' => (string) $year,
                'masuk' => (int) $masukQuery->sum('quantity'),
                'keluar' => (int) $keluarQuery->sum('quantity'),
            ];
        }

        return response()->json(compact('monthlyData', 'yearlyData'));
    }

    /**
     * AJAX: Get stock per category filtered by year
     */
    public function categoryByYear(Request $request)
    {
        $year = $request->get('year');
        $user = auth()->user();
        $saBidang = $this->superAdminBidangContext($request);
        if ($saBidang) {
            $user = $this->createBidangProxy($saBidang);
        }

        if ($user->isTeknik()) {
            return response()->json($this->shipUnloaderStockData($user, $year));
        }

        return response()->json($this->categoryStockData($user, $year));
    }

    private function categoryStockData($user, ?int $year = null): array
    {
        $categories = Item::visibleFor($user)->select('category')->distinct()->pluck('category');
        $data = [];

        foreach ($categories as $cat) {
            $itemIds = Item::visibleFor($user)->where('category', $cat)->pluck('id');

            $masukQuery = Transaction::visibleFor($user)->approved()->masuk()->whereIn('item_id', $itemIds);
            $keluarQuery = Transaction::visibleFor($user)->approved()->keluar()->whereIn('item_id', $itemIds);

            if ($year) {
                $masukQuery->whereYear('date', $year);
                $keluarQuery->whereYear('date', $year);
            }

            $masuk = $masukQuery->sum('quantity');
            $keluar = $keluarQuery->sum('quantity');

            $data[] = [
                'category' => $cat,
                'stock' => max(0, (int) ($masuk - $keluar)),
            ];
        }

        return $data;
    }

    private function technicalTypeSummary($items): array
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

    private function shipUnloaderStockData($user, ?int $year = null): array
    {
        $stockByShip = collect(['1', '2', '3', '4'])->mapWithKeys(fn($ship) => [$ship => 0])->all();
        $items = Item::visibleFor($user)->where('bidang', 'teknik')->get();
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
                ? $this->itemStockForYear($user, $item->id, (int) $year)
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

    private function itemStockForYear($user, int $itemId, int $year): int
    {
        $masuk = Transaction::visibleFor($user)->approved()->masuk()
            ->where('item_id', $itemId)
            ->whereYear('date', $year)
            ->sum('quantity');
        $keluar = Transaction::visibleFor($user)->approved()->keluar()
            ->where('item_id', $itemId)
            ->whereYear('date', $year)
            ->sum('quantity');

        return (int) ($masuk - $keluar);
    }
}
