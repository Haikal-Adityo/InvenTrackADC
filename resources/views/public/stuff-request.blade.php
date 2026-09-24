<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Rekap Stok & Permintaan Barang - NextLog</title>
    <meta name="description" content="Lihat rekap stok barang dan ajukan stuff request tanpa login">

    {{-- Open Graph / link preview (WhatsApp, dll) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NextLog">
    <meta property="og:title" content="NextLog - PT. Artha Daya Coalindo">
    <meta property="og:description" content="Lihat rekap stok barang dan ajukan stuff request tanpa login">
    <meta property="og:image" content="{{ asset('images/logo-web.png') }}">
    <meta property="og:image:width" content="1205">
    <meta property="og:image:height" content="670">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="NextLog - PT. Artha Daya Coalindo">
    <meta name="twitter:description" content="Lihat rekap stok barang dan ajukan stuff request tanpa login">
    <meta name="twitter:image" content="{{ asset('images/logo-web.png') }}">

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preload" as="image" href="{{ asset('images/logo-web.png') }}" fetchpriority="high">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}?v={{ filemtime(public_path('css/custom.css')) }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <!-- Icon Teknik -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Icon NextLog -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-web-top.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#198754">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="NextLog">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    @if($activeBidang === 'teknik')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    @endif
    <script>
        (function() {
            const theme = localStorage.getItem('inventrack-theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

</head>
<body class="public-layout {{ $activeBidang === 'teknik' ? 'public-technical-layout' : '' }}">
    <!-- Animated Nexus Background -->
    <div class="background-glow-container">
        <div class="nexus-bg"></div>
        <div class="nexus-grid"></div>
    </div>
    
    <div class="public-page">
        {{-- Header --}}
        <header class="public-header">
        <div class="container">
            <div class="header-wrapper d-flex align-items-center justify-content-between">
                
                <div class="d-none d-md-block">
                    <div class="company-logo">
                        <img src="{{ asset('images/logo-perusahaan.png') }}" alt="Logo" class="logo-img" loading="lazy" decoding="async">
                    </div>
                </div>

                <div class="brand text-center">
                    <div class="brand-logo-wrapper mx-auto">
                        <img src="{{ asset('images/logo-web.png') }}" alt="InvenTrack Logo" class="app-logo" decoding="async" fetchpriority="high">
                    </div>
                </div>

                <div class="header-actions d-flex align-items-center gap-2">
                    <div class="report-tabs mb-0 me-2" style="background: transparent; border: none; padding: 0; gap: 0.5rem; display: flex;">
                        <a href="{{ route('public.stuff-request', array_merge(request()->except(['bidang', 'page']), ['bidang' => 'umum'])) }}"
                            class="report-tab {{ $activeBidang === 'umum' ? 'active' : '' }}" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; white-space: nowrap;">
                            <i class="bi bi-building me-1"></i>
                            General Affair
                        </a>
                        {{-- <a href="{{ route('public.stuff-request', array_merge(request()->except(['bidang', 'page']), ['bidang' => 'teknik'])) }}"
                            class="report-tab {{ $activeBidang === 'teknik' ? 'active' : '' }}" style="padding: 6px 12px; font-size: 12px; border-radius: 8px; white-space: nowrap;">
                            <i class="bi bi-tools me-1"></i>
                            SU-SpareTracker
                        </a> --}}
                    </div>
                    <button class="btn-theme-toggle" onclick="toggleTheme()" title="Ganti tema">
                        <i class="bi bi-sun-fill icon-sun"></i>
                        <i class="bi bi-moon-fill icon-moon"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal" style="border-radius:8px;padding:8px 16px;font-size:12px;font-weight:600;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </button>
                </div>

            </div>
        </div>
    </header>

        {{-- Body --}}
        <div class="public-body">
            <div class="container">
                @if($activeBidang === 'teknik' && $publicDashboard)
                    <h1 class="fw-bold mb-4">SU-SpareTracker Dashboard</h1>
                    <div class="technical-dashboard-cards mb-4">
                        {{-- Total Spare Parts (SOH) --}}
                        <div class="technical-dashboard-card technical-total-card filter-card-btn active" data-filter="all" style="cursor: pointer;">
                            <div class="technical-dashboard-card-copy">
                                <div class="technical-dashboard-card-title">Total Spare Parts (SOH)</div>
                                <div class="technical-dashboard-card-value">
                                    {{ number_format($publicDashboard['totalItems']) }} <span>Items</span>
                                </div>
                                <div class="technical-dashboard-card-trend {{ $publicDashboard['totalItemsMonthlyChange'] < 0 ? 'is-down' : 'is-up' }}">
                                    <strong>
                                        <i class="bi bi-graph-{{ $publicDashboard['totalItemsMonthlyChange'] < 0 ? 'down' : 'up' }}-arrow"></i>
                                        {{ $publicDashboard['totalItemsMonthlyChange'] > 0 ? '+' : '' }}{{ number_format($publicDashboard['totalItemsMonthlyChange'], 1) }}%
                                    </strong>
                                    <span>vs last month</span>
                                </div>
                            </div>
                            <div class="technical-dashboard-card-icon technical-total-icon">
                                <i class="bi bi-pie-chart-fill"></i>
                            </div>
                        </div>

                        {{-- Critical Stock --}}
                        <div class="technical-dashboard-card technical-critical-card filter-card-btn" data-filter="critical" style="cursor: pointer;">
                            <div class="technical-dashboard-card-copy">
                                <div class="technical-dashboard-card-title">CRITICAL STOCK</div>
                                <div class="technical-dashboard-card-value" style="color: #ef4444;">
                                    {{ number_format($publicDashboard['criticalStockCount']) }} <span>Items</span>
                                </div>
                                <div class="technical-dashboard-card-link">Items requiring restock</div>
                            </div>
                            <div class="technical-dashboard-card-icon technical-critical-icon" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                            </div>
                        </div>

                        {{-- Total Item Types --}}
                        <div class="technical-dashboard-card technical-type-card">
                            <div class="technical-dashboard-card-copy">
                                <div class="technical-type-card-head">
                                    <div>
                                        <div class="technical-dashboard-card-title">Total Item Types</div>
                                        <div class="technical-dashboard-card-subtitle">Categories Registered</div>
                                    </div>
                                    <div class="technical-type-card-total">{{ number_format($publicDashboard['typeSummary']['total_types'] ?? 0) }}</div>
                                </div>
                                <div class="technical-type-card-list">
                                    @forelse(array_slice($publicDashboard['typeSummary']['top_types'] ?? [], 0, 2) as $type)
                                        <div class="technical-type-card-row">
                                            <i class="bi {{ $loop->iteration % 2 === 0 ? 'bi-lightning-fill' : 'bi-gear-fill' }}"></i>
                                            <span>{{ $type['name'] }}</span>
                                            <small>{{ $type['percentage'] }}%</small>
                                        </div>
                                    @empty
                                        <div class="technical-type-card-empty">Belum ada tipe barang</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 public-technical-chart-row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <h4 class="mb-0 fw-bold">Movement Analysis</h4>
                                        <small class="text-secondary" style="font-size: 13px;">GR vs GI tracking trend</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                        <select id="publicMonthlyPeriodFilter" class="form-select form-select-sm"
                                            style="width:auto; min-width:140px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                                            <option value="thisMonth" {{ $publicDashboard['selectedMonthlyPeriod'] === 'thisMonth' ? 'selected' : '' }}>Bulan Ini</option>
                                            <option value="6months" {{ $publicDashboard['selectedMonthlyPeriod'] === '6months' ? 'selected' : '' }}>6 Bulan Terakhir</option>
                                            <option value="ytd" {{ $publicDashboard['selectedMonthlyPeriod'] === 'ytd' ? 'selected' : '' }}>12 Bulan</option>
                                        </select>
                                        <select id="publicMonthlyYearFilter" class="form-select form-select-sm"
                                            style="width:auto; min-width:120px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                                            @foreach($publicDashboard['availableYears'] as $year)
                                                <option value="{{ $year }}" {{ $publicDashboard['selectedYear'] == $year ? 'selected' : '' }}>{{ $year }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:320px;">
                                        <canvas id="publicMonthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div>
                                        <h4 class="mb-0 fw-bold">Asset Distribution</h4>
                                        <small class="text-secondary" style="font-size: 13px;">By Ship Unloader Unit</small>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="height:320px;">
                                        <canvas id="publicShipChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4 technical-soh-activity-row">
                        <div class="col-lg-8">
                            <div class="card technical-soh-detail-card">
                                <div class="card-header">
                                    <h4 class="fw-bold">Detailed Stock On Hand Table</h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table technical-soh-table mb-0">
                                             <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>No. Norm</th>
                                                    <th class="col-name-wrap">Spare Part Name</th>
                                                    <th class="text-end">Volume</th>           
                                                </tr>
                                             </thead>
                                             <tbody>
                                                  @forelse($publicDashboard['detailedSohTransactions'] as $tx)
                                                     <tr>
                                                         <td class="text-secondary" style="font-size: 13px !important;">{{ $tx->date ? $tx->date->format('Y-m-d') : ($tx->created_at ? $tx->created_at->format('Y-m-d') : '-') }}</td>
                                                         <td>
                                                             <span class="{{ $tx->type === 'in' ? 'text-in' : 'text-out' }} fw-bold" style="font-family: 'Roboto Mono', monospace; font-size:16px;">
                                                                 {{ $tx->item?->no_normalisasi ?: 'NORM-' . str_pad($tx->item_id, 4, '0', STR_PAD_LEFT) }}
                                                             </span>
                                                         </td>
                                                         <td class="fw-700 col-name-wrap">{{ $tx->item?->name ?? 'Barang dihapus' }}</td>
                                                         <td class="text-end">
                                                             <div class="d-flex flex-column align-items-end">
                                                                 <span class="{{ $tx->type === 'in' ? 'text-success' : 'text-danger' }} fw-bold">
                                                                     {{ $tx->type === 'in' ? '+' : '-' }}{{ number_format($tx->quantity) }}
                                                                 </span>
                                                                 <span class="text-secondary small text-uppercase" style="font-size: 11px;">
                                                                     {{ $tx->item?->unit ?? 'PCS' }}
                                                                 </span>
                                                             </div>
                                                         </td>
                                                     </tr>
                                                 @empty
                                                     <tr>
                                                         <td colspan="4">
                                                             <div class="empty-state" style="padding:26px 10px;">
                                                                 <i class="bi bi-inbox" style="font-size:34px;"></i>
                                                                 <h6 class="mt-2" style="font-size:13px;">Belum ada data transaksi terbaru</h6>
                                                             </div>
                                                         </td>
                                                     </tr>
                                                 @endforelse
                                             </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card technical-activity-card" style="height: 235px; min-height: 235px;">
                                <div class="card-header">
                                    <h4 class="fw-bold">Recent Activity Feed</h4>
                                </div>
                                <div class="card-body technical-activity-feed">
                                    @forelse($publicDashboard['recentTransactions'] as $tx)
                                        <div class="technical-activity-item">
                                            <div class="technical-activity-icon {{ $tx->type === 'in' ? 'receipt' : 'issue' }}">
                                                <i class="bi bi-{{ $tx->type === 'in' ? 'boxes' : 'box-arrow-up' }}"></i>
                                            </div>
                                            <div class="technical-activity-copy">
                                                <div class="technical-activity-title">
                                                    <strong>{{ $tx->item?->no_normalisasi ?? 'NORM-' . str_pad($tx->item_id, 4, '0', STR_PAD_LEFT) }}</strong>
                                                    - {{ number_format($tx->quantity) }} {{ $tx->item?->unit ?? 'Units' }}
                                                </div>
                                                <div class="technical-activity-detail">
                                                    {{ $tx->type === 'in' ? 'Added' : 'Issued' }}
                                                    @if($tx->item?->lokasi)
                                                        ({{ $tx->item->lokasi }})
                                                    @endif
                                                    -
                                                </div>
                                                <time>[{{ \Carbon\Carbon::parse($tx->date ?? $tx->created_at)->format('Y-m-d') }}]</time>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="empty-state" style="padding:30px 10px;">
                                            <i class="bi bi-inbox" style="font-size:40px;"></i>
                                            <h6 class="mt-2" style="font-size:13px;">Belum ada transaksi</h6>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif



                @if($activeBidang === 'teknik')

                    {{-- Card Filter SOH --}}
                    <div class="soh-filter-cards mb-4" style="margin-top: 2rem;" id="soh-filter-card">
                        <div class="soh-filter-card-slot">
                            <div class="soh-filter-card soh-total active filter-card-btn" data-filter="all" style="cursor: pointer;">
                                <div>
                                    <div class="soh-filter-title">Total SOH Items</div>
                                    <div class="soh-filter-value">
                                        {{ number_format($publicDashboard['totalSoh'] ?? 0) }}
                                        <span>Items</span>
                                    </div>
                                    <div class="soh-filter-caption">Accross all categories</div>
                                </div>
                                <div class="soh-filter-icon">
                                    <i class="bi bi-stack"></i>
                                </div>
                            </div>
                        </div>
                        <div class="soh-filter-card-slot">
                            <div class="soh-filter-card soh-low filter-card-btn" data-filter="low" style="cursor: pointer;">
                                <div>
                                    <div class="soh-filter-title">Low Stock Status</div>
                                    <div class="soh-filter-value">
                                        {{ number_format($publicDashboard['lowStockCount'] ?? 0) }}
                                        <span>Items</span>
                                    </div>
                                    <div class="soh-filter-caption">Requires reorder soon</div>
                                </div>
                                <div class="soh-filter-icon">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                </div>
                            </div>
                        </div>
                        <div class="soh-filter-card-slot">
                            <div class="soh-filter-card soh-critical filter-card-btn" data-filter="critical" style="cursor: pointer;">
                                <div>
                                    <div class="soh-filter-title">Critical Status</div>
                                    <div class="soh-filter-value">
                                        {{ number_format($publicDashboard['criticalStockCount'] ?? 0) }}
                                        <span>Items</span>
                                    </div>
                                    <div class="soh-filter-caption">Immediate purchase required</div>
                                </div>
                                <div class="soh-filter-icon">
                                    <i class="bi bi-radioactive"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" id="public-teknik-table-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="fs-5">Master Stock On Hand (SOH)</span>
                            <div class="d-flex align-items-center gap-2">
                                <form method="GET" action="{{ route('public.stuff-request') }}" id="publicTeknikSearchForm" class="m-0">
                                    <input type="hidden" name="bidang" value="teknik">
                                    <div class="position-relative" id="publicTeknikSearchWrapper">
                                        <input type="text" id="publicTeknikSearch" name="search" class="form-control form-control-sm" placeholder="Cari barang teknik..." style="width: 250px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px;" value="{{ request('search') }}" autocomplete="off">
                                        <div id="publicTeknikSearchSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-container">
                                <table class="table mb-0 align-middle" id="public-teknik-table">
                                    <thead>
                                        <tr>
                                            <th>No. Normalisasi</th>
                                            <th>Nama Spare Part</th>
                                            <th>Komponen</th>
                                            <th>Tipe Barang</th>
                                            <th>Store Room</th>
                                            <th>Ship Unloader</th>
                                            <th class="text-center">Low Limit (Aktual)</th>
                                            <th class="text-center">Min Stock</th>
                                            <th class="text-center">Volume</th>
                                            <th>Satuan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $item)
                                            @php
                                                $activeShips = collect(explode(',', (string) $item->stock_ship_unloader))->filter()->all();
                                                
                                                if ($item->current_stock < $item->min_stock) {
                                                    $statusClass = 'critical';
                                                    $statusLabel = 'Critical';
                                                } elseif ($item->current_stock == $item->min_stock) {
                                                    $statusClass = 'low';
                                                    $statusLabel = 'Low Stock';
                                                } else {
                                                    $statusClass = 'in-stock';
                                                    $statusLabel = 'In Stock';
                                                }
                                            @endphp
                                            <tr data-item-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-normalisasi="{{ $item->no_normalisasi ?? '' }}"
                                                data-component="{{ $item->component ?? '' }}"
                                                data-category="{{ $item->category ?? '' }}"
                                                data-unit="{{ strtolower($item->unit ?? '') }}"
                                                data-status="{{ $statusClass }}">
                                                <td style="font-family: 'Major Mono Display', monospace; font-size:16px;">
                                                    @if($item->current_stock < $item->min_stock)
                                                        <span class="badge-status badge-rejected position-relative badge-critical-teknik" style="font-size:16px;">
                                                            {{ $item->no_normalisasi ?? '-' }}
                                                        </span>
                                                    @elseif($item->current_stock == $item->min_stock)
                                                        <span class="badge-status technical-soh-norm norm-out" style="font-size:16px;">
                                                            {{ $item->no_normalisasi ?? '-' }}
                                                        </span>
                                                    @else
                                                        <span class="badge-status badge-approved" style="font-size:16px;">
                                                            {{ $item->no_normalisasi ?? '-' }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="fw-600 name-cell col-name-wrap">{{ $item->name }}</td>
                                                <td class="component-cell">{{ $item->component ?? '-' }}</td>
                                                <td class="category-cell">{{ $item->category ?? '-' }}</td>
                                                <td class="location-cell">{{ $item->lokasi ?? '-' }}</td>
                                                <td>
                                                    <div class="d-flex flex-nowrap align-items-center gap-1">
                                                        @foreach([1, 2, 3, 4] as $ship)
                                                            <span class="badge {{ in_array((string) $ship, $activeShips, true) ? 'badge-ship-active' : 'badge-ship-inactive' }}">
                                                                {{ $ship }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td class="fw-700 text-center {{ $item->current_stock < $item->min_stock ? 'stock-value-critical' : ($item->current_stock == $item->min_stock ? 'stock-value-low' : 'stock-value-ready') }}">
                                                    {{ number_format($item->current_stock) }}
                                                </td>
                                                <td class="text-center">{{ number_format($item->min_stock) }}</td>
                                                <td class="text-center">{{ $item->volume ?? '-' }}</td>
                                                <td>{{ $item->unit }}</td>
                                                <td>
                                                    @if($statusClass === 'critical')
                                                        <span class="badge-status badge-rejected position-relative badge-critical-teknik">
                                                            Critical
                                                            <span class="ping-container">
                                                                <span class="ping-dot-pulse"></span>
                                                                <span class="ping-dot-core"></span>
                                                            </span>
                                                        </span>
                                                    @elseif($statusClass === 'low')
                                                        <span class="badge-status technical-soh-norm norm-out">
                                                            Low Stock
                                                        </span>
                                                    @else
                                                        <span class="badge-status badge-approved">
                                                            In Stock
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="no-data-row">
                                                <td colspan="11" class="text-center py-4">
                                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                                                    Belum ada data barang teknik
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Goods Receipt Section --}}
                    <div class="row g-4 mb-4">
                        {{-- Goods Receipt Form --}}
                        <div class="col-lg-4">
                            <div class="request-form-card" style="height: 100%;">
                                <div class="request-form-header">
                                    <h4>Goods Receipt</h4>
                                    <p>Registrasi Penerimaan Barang</p>
                                </div>
                                <div class="request-form-body text-uppercase">
                                    <form method="POST" action="{{ route('transactions.store') }}" id="publicGRForm">
                                        @csrf
                                        <input type="hidden" name="type" value="in">

                                        <div class="mb-3">
                                            <label class="form-label">Tanggal GI<span class="text-danger">*</span></label>
                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required min="{{ date('Y-m-d') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">No. Normalisasi</label>
                                            <input type="text" class="form-control" id="publicGRNoNormalisasi" readonly placeholder="00.000.000.0000">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Spare Part <span class="text-danger">*</span></label>
                                            <select name="item_id" class="form-select" required id="publicGRItemSelect">
                                                <option value="">-- Pilih Spare Part --</option>
                                                @foreach($allItems as $item)
                                                    <option value="{{ $item->id }}"
                                                        data-category="{{ $item->category }}"
                                                        data-unit="{{ $item->unit }}"
                                                        data-stock="{{ $item->current_stock }}"
                                                        data-no-normalisasi="{{ $item->no_normalisasi }}"
                                                        data-lokasi="{{ $item->lokasi }}"
                                                        data-component="{{ $item->component }}"
                                                        data-volume="{{ $item->volume }}"
                                                        data-ship-unloader="{{ $item->stock_ship_unloader }}">
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                             <label class="form-label">Tipe Barang</label>
                                             <input type="text" class="form-control" id="publicGRItemCategory" readonly placeholder="Auto Fill">
                                         </div>

                                         <div class="mb-3">
                                             <label class="form-label">Komponen</label>
                                             <input type="text" class="form-control" id="publicGRCategory" readonly placeholder="Auto Fill">
                                         </div>

                                         <div class="mb-3">
                                             <label class="form-label">Stok</label>
                                             <input type="text" class="form-control" id="publicGRStock" readonly placeholder="Auto Fill">
                                         </div>

                                         <div class="mb-3">
                                             <label class="form-label">Store Room</label>
                                             <input type="text" class="form-control" id="publicGRLokasi" readonly placeholder="Auto Fill">
                                         </div>

                                        <div class="mb-3">
                                            <label class="form-label">Ship Unloader <span class="text-danger">*</span></label>
                                            <div class="d-flex flex-nowrap align-items-center gap-1 ship-input-group" id="publicGRShipBadges">
                                                @foreach([1, 2, 3, 4] as $ship)
                                                    <label class="ship-checkbox-label">
                                                        <input class="ship-checkbox-input public-gr-ship-checkbox" type="checkbox" name="ship_unloader[]" value="{{ $ship }}" data-ship="{{ $ship }}" @checked(in_array((string)$ship, old('type') === 'in' ? old('ship_unloader', []) : [], true))>
                                                        <span class="ship-checkbox-box">SU-{{ $ship }}</span>
                                                    </label>
                                                @endforeach
                                                <label class="ship-checkbox-label">
                                                    <input class="ship-checkbox-input" type="checkbox" id="publicGRShipAll" data-ship="all">
                                                    <span class="ship-checkbox-box px-2" style="width: auto; min-width: 24px;">ALL</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                                <input type="number" name="quantity" class="form-control" min="1" required id="publicGRQuantity">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Satuan</label>
                                                <input type="text" class="form-control" id="publicGRUnit" readonly placeholder="Auto Fill">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn w-100 btn-receipt-submit fs-6">
                                           Process Goods Receipt
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        {{-- Goods Receipt History Table --}}
                        <div class="col-lg-8">
                            <div class="card" style="height: 100%;">
                                <div class="card-header">
                                    <span class="fs-5 text-uppercase"><i class="bi bi-table text-success me-2"></i>Recent GR Log</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table mb-0 align-middle" style="min-width: 1000px;">
                                            <thead>
                                                <tr>
                                                    <th style="width:50px;">No</th>
                                                    <th>Tanggal</th>
                                                    <th>No. Normalisasi</th>
                                                    <th class="col-name-wrap">Nama Barang</th>
                                                    <th>Komponen</th>
                                                    <th>Tipe Barang</th>
                                                    <th style="width: 100px;">Ship Unloader</th>
                                                    <th>Store Room</th>
                                                    <th class="text-center">Volume</th>
                                                    <th class="text-center">Jumlah</th>
                                                    <th>Satuan</th>
                                                    <th>User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($publicDashboard['recentReceipts'] as $index => $tx)
                                                    @php
                                                        $activeShips = collect(explode(',', (string) $tx->ship_unloader))->filter()->all();
                                                        $isAllActive = count($activeShips) === 4;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ ($publicDashboard['recentReceipts']->currentPage() - 1) * $publicDashboard['recentReceipts']->perPage() + $index + 1 }}</td>
                                                        <td>{{ $tx->date->format('d/m/Y') }}</td>
                                                        <td><span class="norm-text-in">{{ $tx->no_normalisasi ?? $tx->item->no_normalisasi ?? '-' }}</span></td>
                                                        <td class="fw-600 col-name-wrap">{{ $tx->item->name ?? '-' }}</td>
                                                        <td>{{ $tx->item->component ?? '-' }}</td>
                                                        <td>{{ $tx->item->category ?? '-' }}</td>
                                                        <td class="text-nowrap align-middle" style="width: 100px;">
                                                            <div class="d-flex flex-nowrap align-items-center gap-1">
                                                                @if($isAllActive)
                                                                    <span class="badge badge-all" style="width: auto; min-width: 24px; padding: 0 6px !important;">
                                                                        ALL
                                                                    </span>
                                                                @elseif(count($activeShips) > 0)
                                                                    @foreach($activeShips as $ship)
                                                                        <span class="badge badge-ship-active">
                                                                            {{ $ship }}
                                                                        </span>
                                                                    @endforeach
                                                                @else
                                                                    -
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>{{ $tx->lokasi ?? $tx->item->lokasi ?? '-' }}</td>
                                                        <td class="text-center fw-700">{{ $tx->volume === null ? '-' : number_format($tx->volume) }}</td>
                                                        <td class="text-center fw-700">
                                                            @if($tx->type === 'in')
                                                                <span class="text-success">
                                                                    +{{ number_format($tx->quantity) }}
                                                                </span>
                                                            @else
                                                                <span class="text-danger">
                                                                    -{{ number_format($tx->quantity) }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $tx->item->unit ?? '-' }}</td>
                                                        <td>{{ $tx->user->name ?? 'Guest' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="12">
                                                            <div class="empty-state" style="padding:40px 10px;">
                                                                <i class="bi bi-inbox" style="font-size:34px;"></i>
                                                                <h6 class="mt-2" style="font-size:13px;">Belum ada history penerimaan</h6>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($publicDashboard['recentReceipts']->hasPages())
                                        <div class="px-3 py-2 border-top d-flex justify-content-center">
                                            {{ $publicDashboard['recentReceipts']->appends(request()->except('gr_page'))->links('pagination.custom') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Goods Issue Section --}}
                    <div class="row g-4 mb-4">
                        {{-- Goods Issue Form --}}
                        <div class="col-lg-4">
                            <div class="request-form-card" style="height: 100%;">
                                <div class="request-form-header">
                                    <h4>Goods Issue</h4>
                                    <p>Registrasi Pengeluaran Barang</p>
                                </div>
                                <div class="request-form-body text-uppercase">
                                    <form method="POST" action="{{ route('transactions.store') }}" id="publicGIForm">
                                        @csrf
                                        <input type="hidden" name="type" value="out">

                                        <div class="mb-3">
                                            <label class="form-label">Tanggal GR<span class="text-danger">*</span></label>
                                            <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required min="{{ date('Y-m-d') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">No. Normalisasi</label>
                                            <input type="text" class="form-control" id="publicGINoNormalisasi" readonly placeholder="00.000.000.0000">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Spare Part <span class="text-danger">*</span></label>
                                            <select name="item_id" class="form-select" required id="publicGIItemSelect">
                                                <option value="">-- Pilih Spare Part --</option>
                                                @foreach($allItems as $item)
                                                    <option value="{{ $item->id }}"
                                                        data-category="{{ $item->category }}"
                                                        data-unit="{{ $item->unit }}"
                                                        data-stock="{{ $item->current_stock }}"
                                                        data-no-normalisasi="{{ $item->no_normalisasi }}"
                                                        data-lokasi="{{ $item->lokasi }}"
                                                        data-component="{{ $item->component }}"
                                                        data-volume="{{ $item->volume }}"
                                                        data-ship-unloader="{{ $item->stock_ship_unloader }}">
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Tipe Barang</label>
                                            <input type="text" class="form-control" id="publicGIItemCategory" readonly placeholder="Auto Fill">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Komponen</label>
                                            <input type="text" class="form-control" id="publicGICategory" readonly placeholder="Auto Fill">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Stok</label>
                                            <input type="text" class="form-control" id="publicGIStock" readonly placeholder="Auto Fill">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Store Room</label>
                                            <input type="text" class="form-control" id="publicGILokasi" readonly placeholder="Auto Fill">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Ship Unloader <span class="text-danger">*</span></label>
                                            <div class="d-flex flex-nowrap align-items-center gap-1 ship-input-group" id="publicGIShipBadges">
                                                @foreach([1, 2, 3, 4] as $ship)
                                                    <label class="ship-checkbox-label">
                                                        <input class="ship-checkbox-input public-gi-ship-checkbox" type="checkbox" name="ship_unloader[]" value="{{ $ship }}" data-ship="{{ $ship }}" @checked(in_array((string)$ship, old('type') === 'out' ? old('ship_unloader', []) : [], true))>
                                                        <span class="ship-checkbox-box ship-checkbox-box-issue">SU-{{ $ship }}</span>
                                                    </label>
                                                @endforeach
                                                <label class="ship-checkbox-label">
                                                    <input class="ship-checkbox-input" type="checkbox" id="publicGIShipAll" data-ship="all">
                                                    <span class="ship-checkbox-box px-2" style="width: auto; min-width: 24px;">ALL</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                                <input type="number" name="quantity" class="form-control" min="1" required id="publicGIQuantity">
                                                <div id="publicGIStockWarning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Melebihi stok tersedia.
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Satuan</label>
                                                <input type="text" class="form-control" id="publicGIUnit" readonly placeholder="Auto Fill">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn w-100 btn-issue-submit fs-6">
                                          Process Goods Issue
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        {{-- Goods Issue History Table --}}
                        <div class="col-lg-8">
                            <div class="card" style="height: 100%;">
                                <div class="card-header">
                                    <span class="fs-5 text-uppercase"><i class="bi bi-table text-warning me-2"></i>Recent GI Log</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table mb-0 align-middle" style="min-width: 1000px;">
                                            <thead>
                                                <tr>
                                                    <th style="width:50px;">No</th>
                                                    <th>Tanggal</th>
                                                    <th>No. Normalisasi</th>
                                                    <th class="col-name-wrap">Nama Barang</th>
                                                    <th>Komponen</th>
                                                    <th>Tipe Barang</th>
                                                    <th style="width: 100px;">Ship Unloader</th>
                                                    <th>Store Room</th>
                                                    <th class="text-center">Volume</th>
                                                    <th class="text-center">Jumlah</th>
                                                    <th>Satuan</th>
                                                    <th>User</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($publicDashboard['recentIssues'] as $index => $tx)
                                                    @php
                                                        $activeShips = collect(explode(',', (string) $tx->ship_unloader))->filter()->all();
                                                        $isAllActive = count($activeShips) === 4;
                                                    @endphp
                                                    <tr>
                                                        <td>{{ ($publicDashboard['recentIssues']->currentPage() - 1) * $publicDashboard['recentIssues']->perPage() + $index + 1 }}</td>
                                                        <td>{{ $tx->date->format('d/m/Y') }}</td>
                                                        <td><span class="norm-text-out">{{ $tx->no_normalisasi ?? $tx->item->no_normalisasi ?? '-' }}</span></td>
                                                        <td class="fw-600 col-name-wrap">{{ $tx->item->name ?? '-' }}</td>
                                                        <td>{{ $tx->item->component ?? '-' }}</td>
                                                        <td>{{ $tx->item->category ?? '-' }}</td>
                                                        <td class="text-nowrap align-middle" style="width: 100px;">
                                                            <div class="d-flex flex-nowrap align-items-center gap-1">
                                                                @if($isAllActive)
                                                                    <span class="badge badge-all" style="width: auto; min-width: 24px; padding: 0 6px !important;">
                                                                        ALL
                                                                    </span>
                                                                @elseif(count($activeShips) > 0)
                                                                    @foreach($activeShips as $ship)
                                                                        <span class="badge badge-ship-active badge-ship-active-issue">
                                                                            {{ $ship }}
                                                                        </span>
                                                                    @endforeach
                                                                @else
                                                                    -
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td>{{ $tx->lokasi ?? $tx->item->lokasi ?? '-' }}</td>
                                                        <td class="text-center fw-700">{{ $tx->volume === null ? '-' : number_format($tx->volume) }}</td>
                                                        <td class="text-center fw-700">
                                                            @if($tx->type === 'in')
                                                                <span class="text-success">
                                                                    +{{ number_format($tx->quantity) }}
                                                                </span>
                                                            @else
                                                                <span class="text-danger">
                                                                    -{{ number_format($tx->quantity) }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $tx->item->unit ?? '-' }}</td>
                                                        <td>{{ $tx->user->name ?? 'Guest' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="12">
                                                            <div class="empty-state" style="padding:40px 10px;">
                                                                <i class="bi bi-inbox" style="font-size:34px;"></i>
                                                                <h6 class="mt-2" style="font-size:13px;">Belum ada history pengeluaran</h6>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if($publicDashboard['recentIssues']->hasPages())
                                        <div class="px-3 py-2 border-top d-flex justify-content-center">
                                            {{ $publicDashboard['recentIssues']->appends(request()->except('gi_page'))->links('pagination.custom') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                @else
                <div class="row g-4">
                    {{-- Left: Stock Recap --}}
                    <div class="col-lg-8">
                        <div class="section-title">
                            <i class="bi bi-clipboard-data-fill"></i> Daftar Barang
                        </div>

                        {{-- Filter --}}
                        <div class="filter-bar mb-3">
                            <form method="GET" action="{{ route('public.stuff-request') }}">
                                <input type="hidden" name="bidang" value="{{ $activeBidang }}">
                                <div class="row g-2 align-items-center">
                                    <div class="col">
                                        <div class="position-relative" id="publicUmumSearchWrapper">
                                            <input
                                                type="text"
                                                id="publicUmumSearch"
                                                name="search"
                                                class="form-control"
                                                placeholder="Cari nama barang, kategori, atau satuan"
                                                value="{{ request('search') }}"
                                                autocomplete="off">

                                            <div id="publicUmumSearchSuggestions"
                                                class="autocomplete-suggestions"
                                                style="display:none;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-auto">
                                        <a href="{{ route('public.stuff-request', ['bidang' => $activeBidang]) }}"
                                            class="btn btn-outline-reset">
                                            <i class="bi bi-arrow-counterclockwise fs-5"></i>
                                        </a>
                                    </div>

                                </div>
                            </form>
                        </div>

                        {{-- Table --}}
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-container">
                                    <table class="table" id="stock-recap-table">
                                        <thead>
                                            <tr>
                                                <th style="width:45px;">No</th>
                                                @if($activeBidang === 'teknik')
                                                    <th>No. Normalisasi</th>
                                                @endif
                                                <th class="col-name-wrap">Nama Barang</th>
                                                <th>{{ $activeBidang === 'teknik' ? 'Tipe Barang' : 'Kategori' }}</th>
                                                @if($activeBidang === 'teknik')
                                                    <th>Store Room</th>
                                                @endif
                                                <th>Satuan</th>
                                                <th class="text-center">Stok</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($items as $index => $item)
                                            @php $stock = $item->current_stock; @endphp
                                            <tr data-item-id="{{ $item->id }}"
                                                data-name="{{ strtolower($item->name) }}"
                                                data-category="{{ strtolower($item->category ?? '') }}"
                                                data-unit="{{ strtolower($item->unit ?? '') }}">
                                                <td>{{ $items->firstItem() + $index }}</td>
                                                @if($activeBidang === 'teknik')
                                                    <td class="fw-600">{{ $item->no_normalisasi ?? '-' }}</td>
                                                @endif
                                                <td class="fw-600 col-name-wrap">{{ $item->name }}</td>
                                                <td>{{ $activeBidang === 'teknik' ? ($item->category ?? '-') : $item->category }}</td>
                                                @if($activeBidang === 'teknik')
                                                    <td>{{ $item->lokasi ?? '-' }}</td>
                                                @endif
                                                <td>{{ $item->unit }}</td>
                                                <td class="text-center fw-700" style="font-size:15px; {{ $stock <= 0 ? 'color:var(--danger);' : ($stock <= $item->min_stock ? 'color:var(--warning-dark);' : 'color:var(--success);') }}">
                                                    {{ number_format($stock) }}
                                                </td>
                                                <td>
                                                    @if($stock <= 0)
                                                        <span class="stock-status-badge stock-empty"><i class="bi bi-x-circle-fill"></i> Habis</span>
                                                    @elseif($stock <= $item->min_stock)
                                                        <span class="stock-status-badge stock-low"><i class="bi bi-exclamation-triangle-fill"></i> Rendah</span>
                                                    @else
                                                        <span class="stock-status-badge stock-ok"><i class="bi bi-check-circle-fill"></i> Ada</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr class="no-data-row">
                                                <td colspan="{{ $activeBidang === 'teknik' ? 8 : 6 }}">
                                                    <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                                    Belum ada data barang
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Pagination GA --}}
                        @if($activeBidang === 'umum' && $items->hasPages())
                            <div class="d-flex justify-content-center mt-3">
                                {{ $items->appends(request()->except('umum_page'))->links('pagination.custom') }}
                            </div>
                        @endif
                    </div>

                    {{-- Right: Request Form --}}

                    <div class="col-lg-4">
                        <div class="section-title">
                            <i class="bi bi-send-fill"></i> Permintaan Barang
                        </div>

                        <div class="request-form-card">
                            <div class="request-form-header">
                                <h5><i class="bi bi-plus-circle me-2"></i>Form Permintaan Barang</h5>
                                <p>Ajukan permintaan penambahan barang tanpa login</p>
                            </div>
                            <div class="request-form-body">
                                <form method="POST" action="{{ route('public.stuff-request.store') }}" id="requestForm">
                                    @csrf
                                    <input type="hidden" name="bidang" value="{{ $activeBidang }}">

                                    <div class="mb-3">
                                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" name="requester_name" class="form-control" placeholder="Masukkan nama lengkap" value="{{ old('requester_name') }}" required>
                                        @error('requester_name')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">NIP <span class="text-danger">*</span></label>
                                        <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP"
                                            value="{{ old('nip') }}" required>
                                        @error('nip')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Jabatan/Posisi <span class="text-danger">*</span></label>
                                        <input type="text" name="jabatan" class="form-control" placeholder="Masukkan jabatan"
                                            value="{{ old('jabatan') }}" required>
                                        @error('jabatan')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bidang Tujuan</label>
                                        <input type="text" class="form-control" value="Bidang {{ ucfirst($activeBidang) }}" readonly>
                                        @error('bidang')
                                            <div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @php
                                        $lineRows = old('lines', [['item_id' => '', 'quantity' => 1]]);
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label d-flex justify-content-between align-items-center">
                                            <span>Daftar Barang <span class="text-danger">*</span></span>
                                        </label>
                                        <p class="small text-muted mb-2" style="font-size:12px;">Pilih satu atau lebih barang beserta jumlahnya.</p>
                                        <div id="requestLines" class="d-flex flex-column gap-2">
                                            @foreach($lineRows as $i => $line)
                                                <div class="request-line-row border rounded-3 p-2" style="border-color:var(--border-color, #dee2e6) !important;background:var(--card-bg-subtle, transparent);">
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-12 col-md-7">
                                                            <label class="form-label small mb-1 text-muted">Barang</label>
                                                            <select name="lines[{{ $i }}][item_id]" class="form-select form-select-sm" data-field="item" required>
                                                                <option value="">-- Pilih barang --</option>
                                                                @foreach($allItems as $it)
                                                                    <option value="{{ $it->id }}"
                                                                        data-stock="{{ $it->current_stock }}"
                                                                        data-unit="{{ $it->unit }}"
                                                                        @selected((string) old('lines.'.$i.'.item_id', $line['item_id'] ?? '') === (string) $it->id)>
                                                                        {{ $it->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <div class="small text-muted mt-1" data-field="stock-info"></div>
                                                        </div>
                                                        <div class="col-6 col-md-3">
                                                            <label class="form-label small mb-1 text-muted">Jumlah</label>
                                                            <input type="number" name="lines[{{ $i }}][quantity]" data-field="qty" class="form-control form-control-sm" min="1" placeholder="Qty" value="{{ old('lines.'.$i.'.quantity', $line['quantity'] ?? 1) }}" required>
                                                            <div class="text-danger mt-1 d-none" data-field="stock-error" style="font-size:11px;"></div>
                                                        </div>
                                                        <div class="col-6 col-md-2 text-md-end pb-md-1">
                                                            <button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-line" title="Hapus baris">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @error('lines')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                        @foreach ($errors->keys() as $key)
                                            @if (str_starts_with($key, 'lines.') && $errors->first($key))
                                                <div class="text-danger mt-1" style="font-size:12px;">{{ $errors->first($key) }}</div>
                                            @endif
                                        @endforeach
                                        <button type="button" class="btn btn-outline-primary btn-sm mt-2 w-100" id="addLineBtn">
                                            <i class="bi bi-plus-lg"></i> Tambah barang
                                        </button>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Kebutuhan</label>
                                        <textarea name="notes" class="form-control" rows="3" placeholder="Alasan request atau keterangan tambahan...">{{ old('notes') }}</textarea>
                                        @error('notes')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100" id="requestSubmitBtn">
                                        <i class="bi bi-send-fill me-1"></i> Kirim Request
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Info box --}}
                        <div class="card mt-3">
                            <div class="card-body" style="font-size:13px;">
                                <h6 style="font-weight:700;margin-bottom:10px;color:var(--text-primary);">
                                    <i class="bi bi-info-circle-fill text-primary-custom me-1"></i> Informasi
                                </h6>
                                <ul style="margin:0;padding-left:18px;color:var(--text-secondary);line-height:2;">
                                    <li>Request akan ditinjau oleh Admin</li>
                                    <li>Tidak memerlukan login</li>
                                    <li>Stok diperbarui secara real-time</li>
                                    <li>Status: <span class="stock-status-badge stock-ok" style="font-size:10px;">Ada</span>
                                        <span class="stock-status-badge stock-low" style="font-size:10px;">Rendah</span>
                                        <span class="stock-status-badge stock-empty" style="font-size:10px;">Habis</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div> {{-- End .public-page --}}

    <!-- Public footer -->
    <footer class="public-footer">
        <div class="footer-content">
            <img src="{{ asset('images/footer-ship.png') }}" alt="Logo Kapal" class="footer-ship-img" loading="lazy">
            <div class="copyright">
                &copy; 2026 Port Management Unit Suralaya
            </div>
        </div>
    </footer>
    <!-- End Public footer -->

    {{-- Login Modal (placed outside .public-page to avoid z-index stacking context issues) --}}
        <div class="modal fade inventrack-modal login-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
                <div class="modal-content" style="position:relative;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 15px; right: 15px; z-index: 1051;"></button>

                    <div class="modal-loading-overlay" id="loginLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    
                    <div class="login-modal-brand">
                        <div class="brand-icon">
                            <img src="{{ asset('images/logo-web.png') }}" alt="InvenTrack Logo" class="login-modal-logo-img" loading="lazy" decoding="async">
                        </div>
                        <div class="logo-container">
                            <div class="next-logistic">
                                NEXTLOGISTIC
                            </div>
                        </div>
                    </div>

                    <div class="modal-body" style="max-height:none;">
                        <div class="modal-error-alert" id="loginError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="loginErrorMsg"></span>
                        </div>
                        <form id="loginForm" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus id="loginUsername" autocomplete="username">
                            </div>
                            <div class="mb-3 pb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password" required id="loginPassword" style="border-right: none;">
                                    <span class="input-group-text" id="togglePassword" style="cursor: pointer; border-left: none;">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-30 mx-auto d-block" id="loginSubmitBtn">
                                <i class="fa-solid fa-unlock-keyhole text-center"></i> Secure Login
                            </button>
                            <button type="button" class="btn btn-link w-100 mt-2 p-0" id="showForgotPasswordBtn">
                                Lupa password?
                            </button>
                        </form>

                        <form id="forgotPasswordForm" class="d-none" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required id="resetEmail" autocomplete="email">
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="forgotPasswordSubmitBtn">
                                <i class="bi bi-send"></i> Kirim Link Reset
                            </button>
                            <button type="button" class="btn btn-outline-secondary w-100 mt-2" id="backToLoginBtn">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </button>
                        </form>
                    </div>
                    <div class="modal-footer justify-content-center" style="border-top:none;background:transparent;padding-top:0;">
                        <span style="font-size:12px;color:var(--text-muted);">&copy; {{ date('Y') }} Port Managemen Unit Suralaya</span>
                    </div>
                </div>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if($activeBidang === 'teknik' && $publicDashboard)
        <script>
            (function() {
                if (typeof Chart === 'undefined') return;

                const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
                const chartTextColor = () => isDark() ? '#cbd5e1' : '#475569';
                const chartMutedColor = () => isDark() ? '#64748b' : '#94a3b8';
                const chartGridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                const chartSurfaceColor = () => isDark() ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
                const chartCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
                 const catColors = ['#da3e23ff', '#3b82f6', '#8b5cf6', '#10b981', '#f59e0b'];

                function getCategoryColor(label, index) {
                    if (label === 'ALL') return '#da3e23ff'; // Slate gray
                    if (label === 'SU-1') return '#3b82f6'; // Blue
                    if (label === 'SU-2') return '#8b5cf6'; // Purple
                    if (label === 'SU-3') return '#10b981'; // Green
                    if (label === 'SU-4') return '#f59e0b'; // Orange
                    
                    const fallbackColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#06b6d4', '#ef4444', '#14b8a6', '#ec4899'];
                    return fallbackColors[index % fallbackColors.length];
                }

                function lineGradient(ctx, type) {
                    const gradient = ctx.createLinearGradient(0, 0, 0, 320);
                    const prefix = type === 'gi' ? '--chart-gi' : '--chart-gr';
                    gradient.addColorStop(0, chartCssVar(`${prefix}-fill-start`));
                    gradient.addColorStop(0.65, chartCssVar(`${prefix}-fill-mid`));
                    gradient.addColorStop(1, chartCssVar(`${prefix}-fill-end`));
                    return gradient;
                }

                function hexToRgba(hex, alpha) {
                    const value = hex.replace('#', '');
                    const r = parseInt(value.substring(0, 2), 16);
                    const g = parseInt(value.substring(2, 4), 16);
                    const b = parseInt(value.substring(4, 6), 16);
                    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                }

                function donutGradient(ctx, color) {
                    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, hexToRgba(color, chartCssVar('--chart-donut-alpha-start')));
                    gradient.addColorStop(1, hexToRgba(color, chartCssVar('--chart-donut-alpha-end')));
                    return gradient;
                }

                function tooltipOptions() {
                    return {
                        mode: 'index',
                        intersect: false,
                        usePointStyle: true,
                        backgroundColor: chartSurfaceColor(),
                        titleColor: isDark() ? '#f8fafc' : '#0f172a',
                        bodyColor: chartTextColor(),
                        borderColor: isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6
                    };
                }

                function lineOptions() {
                    return {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 15,
                                    color: chartTextColor(),
                                    font: { family: 'Inter', size: 12, weight: 500 }
                                }
                            },
                            tooltip: tooltipOptions()
                        },
                        scales: {
                            x: {
                                grid: { display: false, drawBorder: false },
                                ticks: { color: chartMutedColor(), padding: 10, font: { family: 'Inter', size: 11 } }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: chartGridColor(), drawBorder: false },
                                ticks: { color: chartMutedColor(), padding: 10, font: { family: 'Inter', size: 11 } }
                            }
                        },
                        interaction: { mode: 'nearest', axis: 'x', intersect: false }
                    };
                }

                let publicMonthlyChart = null;
                let publicShipChart = null;
                const monthlyCtx = document.getElementById('publicMonthlyChart')?.getContext('2d');
                if (monthlyCtx) {
                    publicMonthlyChart = new Chart(monthlyCtx, {
                        type: 'line',
                        data: {
                            labels: {!! json_encode(array_column($publicDashboard['monthlyData'], 'label')) !!},
                            datasets: [
                                {
                                    label: 'Goods Receipt',
                                    data: {!! json_encode(array_column($publicDashboard['monthlyData'], 'masuk')) !!},
                                    fill: true,
                                    backgroundColor: lineGradient(monthlyCtx, 'gr'),
                                    borderColor: '#3b82f6',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointBackgroundColor: chartCssVar('--chart-point-bg'),
                                    pointBorderColor: '#3b82f6',
                                    pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                                    pointHoverBorderColor: '#3b82f6',
                                    pointBorderWidth: 2,
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                },
                                {
                                    label: 'Goods Issue',
                                    data: {!! json_encode(array_column($publicDashboard['monthlyData'], 'keluar')) !!},
                                    fill: true,
                                    backgroundColor: lineGradient(monthlyCtx, 'gi'),
                                    borderColor: '#10b981',
                                    borderWidth: 2,
                                    tension: 0.4,
                                    pointBackgroundColor: chartCssVar('--chart-point-bg'),
                                    pointBorderColor: '#10b981',
                                    pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                                    pointHoverBorderColor: '#10b981',
                                    pointBorderWidth: 2,
                                    pointRadius: 5,
                                    pointHoverRadius: 7
                                }
                            ]
                        },
                        options: lineOptions()
                    });
                }

                const shipCtx = document.getElementById('publicShipChart')?.getContext('2d');
                if (shipCtx) {
                    const initialLabels = {!! json_encode(array_column($publicDashboard['categoryData'], 'category')) !!};
                    publicShipChart = new Chart(shipCtx, {
                        type: 'doughnut',
                        data: {
                            labels: initialLabels,
                            datasets: [{
                                data: {!! json_encode(array_column($publicDashboard['categoryData'], 'stock')) !!},
                                backgroundColor: initialLabels.map((cat, idx) => donutGradient(shipCtx, getCategoryColor(cat, idx))),
                                borderColor: initialLabels.map((cat, idx) => getCategoryColor(cat, idx)),
                                borderWidth: 2,
                                hoverOffset: 6,
                                spacing: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        padding: 15,
                                        color: chartMutedColor(),
                                        font: { family: 'Inter', size: 11, weight: 500 },
                                        generateLabels: function(chart) {
                                            const data = chart.data;
                                            if (data.labels.length && data.datasets.length) {
                                                const dataset = data.datasets[0];
                                                const total = dataset.data.reduce((sum, val) => sum + val, 0);
                                                return data.labels.map((label, i) => {
                                                    const value = dataset.data[i] || 0;
                                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                                    const displayLabel = label === 'ALL' ? 'ALL (Common)' : label;
                                                    const text = `${displayLabel} (${percentage}%)`;
                                                    
                                                    return {
                                                        text: text,
                                                        fillStyle: dataset.backgroundColor[i],
                                                        strokeStyle: dataset.borderColor[i],
                                                        lineWidth: dataset.borderWidth || 0,
                                                        hidden: isNaN(dataset.data[i]) || chart.getDatasetMeta(0).data[i].hidden,
                                                        index: i,
                                                        fontColor: chartMutedColor()
                                                    };
                                                });
                                            }
                                            return [];
                                        }
                                    }
                                },
                                tooltip: {
                                    ...tooltipOptions(),
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            const dataset = context.chart.data.datasets[0].data;
                                            const total = dataset.reduce((sum, val) => sum + val, 0);
                                            const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                            return ` ${label}: ${value.toLocaleString()} (${percentage}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                window.refreshPublicDashboardCharts = function() {
                    if (publicMonthlyChart) {
                        publicMonthlyChart.options.plugins.legend.labels.color = chartTextColor();
                        publicMonthlyChart.options.plugins.tooltip.backgroundColor = chartSurfaceColor();
                        publicMonthlyChart.options.plugins.tooltip.titleColor = isDark() ? '#f8fafc' : '#0f172a';
                        publicMonthlyChart.options.plugins.tooltip.bodyColor = chartTextColor();
                        publicMonthlyChart.options.plugins.tooltip.borderColor = isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)';
                        publicMonthlyChart.options.scales.x.ticks.color = chartMutedColor();
                        publicMonthlyChart.options.scales.y.ticks.color = chartMutedColor();
                        publicMonthlyChart.options.scales.y.grid.color = chartGridColor();
                        publicMonthlyChart.data.datasets[0].backgroundColor = lineGradient(monthlyCtx, 'gr');
                        publicMonthlyChart.data.datasets[1].backgroundColor = lineGradient(monthlyCtx, 'gi');
                        publicMonthlyChart.data.datasets.forEach(dataset => {
                            dataset.pointBackgroundColor = chartCssVar('--chart-point-bg');
                            dataset.pointHoverBackgroundColor = chartCssVar('--chart-point-hover-bg');
                        });
                        publicMonthlyChart.update();
                    }

                    if (publicShipChart) {
                        publicShipChart.options.plugins.legend.labels.color = chartMutedColor();
                        publicShipChart.options.plugins.tooltip.backgroundColor = chartSurfaceColor();
                        publicShipChart.options.plugins.tooltip.titleColor = isDark() ? '#f8fafc' : '#0f172a';
                        publicShipChart.options.plugins.tooltip.bodyColor = chartTextColor();
                        publicShipChart.options.plugins.tooltip.borderColor = isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)';
                        publicShipChart.data.datasets[0].backgroundColor = publicShipChart.data.labels
                            .map((cat, idx) => donutGradient(shipCtx, getCategoryColor(cat, idx)));
                        publicShipChart.data.datasets[0].borderColor = publicShipChart.data.labels
                            .map((cat, idx) => getCategoryColor(cat, idx));
                        publicShipChart.update();
                    }
                };

                function changePublicMonthlyChart() {
                    if (!publicMonthlyChart) return;

                    const year = document.getElementById('publicMonthlyYearFilter')?.value || @json($publicDashboard['selectedYear']);
                    const period = document.getElementById('publicMonthlyPeriodFilter')?.value || 'thisMonth';
                    const url = `{{ request()->getBaseUrl() }}/public/api/teknik/monthly-data?year=${encodeURIComponent(year)}&period=${encodeURIComponent(period)}`;

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            publicMonthlyChart.data.labels = data.map(d => d.label);
                            publicMonthlyChart.data.datasets[0].data = data.map(d => d.masuk);
                            publicMonthlyChart.data.datasets[1].data = data.map(d => d.keluar);
                            publicMonthlyChart.update('active');
                        })
                        .catch(err => console.error(err));
                }

                function changePublicShipChart() {
                    if (!publicShipChart) return;

                    const year = document.getElementById('publicShipYearFilter')?.value || '';
                    const url = year
                        ? `{{ request()->getBaseUrl() }}/public/api/teknik/ship-unloader-data?year=${encodeURIComponent(year)}`
                        : `{{ request()->getBaseUrl() }}/public/api/teknik/ship-unloader-data`;

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            publicShipChart.data.labels = data.map(d => d.category);
                            publicShipChart.data.datasets[0].data = data.map(d => d.stock);
                            publicShipChart.data.datasets[0].backgroundColor = data.map((d, idx) => donutGradient(shipCtx, getCategoryColor(d.category, idx)));
                            publicShipChart.data.datasets[0].borderColor = data.map((d, idx) => getCategoryColor(d.category, idx));
                            publicShipChart.update('active');
                        })
                        .catch(err => console.error(err));
                }

                document.getElementById('publicMonthlyPeriodFilter')?.addEventListener('change', changePublicMonthlyChart);
                document.getElementById('publicMonthlyYearFilter')?.addEventListener('change', changePublicMonthlyChart);
                document.getElementById('publicShipYearFilter')?.addEventListener('change', changePublicShipChart);
            })();
        </script>
    @endif
    <script>
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('inventrack-theme', newTheme);

            if (typeof window.refreshPublicDashboardCharts === 'function') {
                window.refreshPublicDashboardCharts();
            }
        }

        // Interactive SOH Card Filters and Search for Teknik
        (function() {
            const filterCards = document.querySelectorAll('.filter-card-btn');
            const searchInput = document.getElementById('publicTeknikSearch');
            const tableRows = document.querySelectorAll('#public-teknik-table tbody tr:not(.no-data-row)');
            let currentFilter = 'all';

            function filterTable() {
                const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

                tableRows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    const textContent = row.textContent.toLowerCase();

                    const matchesFilter = (currentFilter === 'all' || rowStatus === currentFilter);
                    const matchesSearch = (!searchVal || textContent.includes(searchVal));

                    if (matchesFilter && matchesSearch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            filterCards.forEach(card => {
                card.addEventListener('click', function() {
                    const filterValue = this.getAttribute('data-filter');
                    filterCards.forEach(c => {
                        if (c.getAttribute('data-filter') === filterValue) {
                            c.classList.add('active');
                        } else {
                            c.classList.remove('active');
                        }
                    });
                    currentFilter = filterValue;
                    filterTable();
                    
                    // Smooth scroll to table card
                    const tableCard = document.getElementById('soh-filter-card');
                    if (tableCard) {
                        tableCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', filterTable);
            }
        })();

        // Autocomplete search suggestions for Public page (Teknik & Umum)
        (function() {
            function setupPublicAutocomplete(inputId, suggestionsId, wrapperId, tableId, isTeknikSection) {
                const searchInput = document.getElementById(inputId);
                const suggestionsBox = document.getElementById(suggestionsId);
                const searchWrapper = document.getElementById(wrapperId);
                
                if (!searchInput || !suggestionsBox || !searchWrapper) return;

                function filterTableById(itemId) {
                    const rows = document.querySelectorAll('#' + tableId + ' tbody tr:not(.no-data-row)');
                    rows.forEach(row => {
                        row.style.display = row.dataset.itemId === itemId ? '' : 'none';
                    });
                    suggestionsBox.style.display = 'none';
                }

                function resetTable() {
                    const rows = document.querySelectorAll('#' + tableId + ' tbody tr:not(.no-data-row)');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                }

                searchInput.addEventListener('input', function() {
                    const keyword = this.value.trim().toLowerCase();
                    if (!keyword) {
                        resetTable();
                        suggestionsBox.style.display = 'none';
                        return;
                    }

                    const rows = document.querySelectorAll('#' + tableId + ' tbody tr:not(.no-data-row)');
                    let results = [];

                    rows.forEach(row => {
                        const name = (row.dataset.name || '').toLowerCase();
                        const category = (row.dataset.category || '').toLowerCase();
                        const component = (row.dataset.component || '').toLowerCase();
                        const normalisasi = (row.dataset.normalisasi || '').toLowerCase();
                        const unit = (row.dataset.unit || '').toLowerCase();

                        let matched = false;
                        if (isTeknikSection) {
                            matched = name.includes(keyword) || component.includes(keyword) || normalisasi.includes(keyword) || unit.includes(keyword);
                        } else {
                            matched = name.includes(keyword) || category.includes(keyword) || unit.includes(keyword);
                        }

                        if (matched) {
                            results.push({
                                id: row.dataset.itemId,
                                name: row.dataset.name || row.querySelector('.name-cell')?.textContent.trim() || '',
                                category: row.dataset.category || '',
                                component: row.dataset.component || '',
                                normalisasi: row.dataset.normalisasi || ''
                            });
                        }
                    });

                    renderSuggestions(results);
                });

                function renderSuggestions(items) {
                    if (!items.length) {
                        suggestionsBox.innerHTML = '<div class="autocomplete-no-result">Tidak ada barang ditemukan</div>';
                        suggestionsBox.style.display = 'block';
                        return;
                    }

                    suggestionsBox.innerHTML = items.map(item => `
                        <div class="autocomplete-item" data-id="${item.id}">
                            <div class="autocomplete-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="autocomplete-content">
                                <div class="autocomplete-title">${item.name}</div>
                                <div class="autocomplete-subtitle">
                                    ${isTeknikSection ? `${item.normalisasi || '-'} • ${item.component || '-'}` : `${item.category || '-'}`}
                                </div>
                            </div>
                        </div>
                    `).join('');

                    suggestionsBox.style.display = 'block';

                    suggestionsBox.querySelectorAll('.autocomplete-item').forEach(el => {
                        el.addEventListener('click', function() {
                            const name = this.querySelector('.autocomplete-title').textContent.trim();
                            searchInput.value = name;
                            filterTableById(this.dataset.id);
                        });
                    });
                }

                // Handle click outside to close
                document.addEventListener('click', function (e) {
                    if (searchWrapper && !searchWrapper.contains(e.target)) {
                        suggestionsBox.style.display = 'none';
                    }
                });

                // Show suggestion again when focused
                searchInput.addEventListener('focus', function () {
                    if (this.value.trim() !== '' && suggestionsBox.innerHTML.trim() !== '') {
                        suggestionsBox.style.display = 'block';
                    }
                });
            }

            setupPublicAutocomplete('publicTeknikSearch', 'publicTeknikSearchSuggestions', 'publicTeknikSearchWrapper', 'public-teknik-table', true);
            setupPublicAutocomplete('publicUmumSearch', 'publicUmumSearchSuggestions', 'publicUmumSearchWrapper', 'stock-recap-table', false);
        })();

        // Auto-fill and validation logic for public Goods Receipt and Goods Issue forms
        (function() {
            function setupFormAutoFill(selectId, normalisasiId, categoryId, itemCategoryId, stockId, volumeId, lokasiId, unitId, qtyId, warningId, isOutType) {
                const select = document.getElementById(selectId);
                const normalisasi = document.getElementById(normalisasiId);
                const category = document.getElementById(categoryId);
                const itemCategory = document.getElementById(itemCategoryId);
                const stock = document.getElementById(stockId);
                const volume = document.getElementById(volumeId);
                const lokasi = document.getElementById(lokasiId);
                const unit = document.getElementById(unitId);
                const qtyInput = document.getElementById(qtyId);
                const warning = document.getElementById(warningId);

                if (!select) return;

                function checkStock() {
                    if (!qtyInput || !stock || !warning) return;
                    const stockVal = parseInt(stock.value || '0') || 0;
                    const qtyVal = parseInt(qtyInput.value || '0') || 0;
                    warning.style.display = isOutType && select.value && qtyVal > stockVal ? 'block' : 'none';
                }

                select.addEventListener('change', function() {
                    const hasItem = Boolean(select.value);
                    const selected = select.options[select.selectedIndex];

                    if (normalisasi) normalisasi.value = hasItem ? (selected.dataset.noNormalisasi || '') : '';
                    if (category) category.value = hasItem ? (selected.dataset.component || '') : '';
                    if (itemCategory) itemCategory.value = hasItem ? (selected.dataset.category || '') : '';
                    if (stock) stock.value = hasItem ? (selected.dataset.stock || '0') : '';
                    if (volume) volume.value = hasItem ? (selected.dataset.volume || '') : '';
                    if (lokasi) lokasi.value = hasItem ? (selected.dataset.lokasi || '') : '';
                    if (unit) unit.value = hasItem ? (selected.dataset.unit || '') : '';

                    checkStock();
                });

                if (qtyInput) {
                    qtyInput.addEventListener('input', checkStock);
                }
            }

            setupFormAutoFill('publicGRItemSelect', 'publicGRNoNormalisasi', 'publicGRCategory', 'publicGRItemCategory', 'publicGRStock', 'publicGRVolume', 'publicGRLokasi', 'publicGRUnit', 'publicGRQuantity', null, false);
            setupFormAutoFill('publicGIItemSelect', 'publicGINoNormalisasi', 'publicGICategory', 'publicGIItemCategory', 'publicGIStock', 'publicGIVolume', 'publicGILokasi', 'publicGIUnit', 'publicGIQuantity', 'publicGIStockWarning', true);

            function setupShipCheckboxes(allCheckboxId, checkboxClass, formId) {
                const allCheckbox = document.getElementById(allCheckboxId);
                const checkboxes = document.querySelectorAll('.' + checkboxClass);
                const form = document.getElementById(formId);

                if (!allCheckbox) return;

                // On load, if all 4 individual checkboxes are checked, check ALL and uncheck them.
                if (checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked)) {
                    allCheckbox.checked = true;
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                    });
                }

                allCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        checkboxes.forEach(cb => {
                            cb.checked = false;
                        });
                    }
                });

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        if (this.checked) {
                            allCheckbox.checked = false;
                        }
                    });
                });

                if (form) {
                    form.addEventListener('submit', function() {
                        if (allCheckbox.checked) {
                            checkboxes.forEach(cb => {
                                cb.checked = true;
                            });
                        }
                    });
                }
            }

            setupShipCheckboxes('publicGRShipAll', 'public-gr-ship-checkbox', 'publicGRForm');
            setupShipCheckboxes('publicGIShipAll', 'public-gi-ship-checkbox', 'publicGIForm');
        })();

        // Multi-line barang: tambah / hapus baris
        (function() {
            const container = document.getElementById('requestLines');
            const addBtn = document.getElementById('addLineBtn');
            const form = document.getElementById('requestForm');
            const submitBtn = document.getElementById('requestSubmitBtn');
            if (!container || !addBtn) return;

            function reindexLineNames() {
                container.querySelectorAll('.request-line-row').forEach((row, idx) => {
                    const sel = row.querySelector('[data-field="item"]');
                    const qty = row.querySelector('[data-field="qty"]');
                    if (sel) sel.name = 'lines[' + idx + '][item_id]';
                    if (qty) qty.name = 'lines[' + idx + '][quantity]';
                });
            }

            function getSelectedOption(row) {
                const select = row.querySelector('[data-field="item"]');
                return select && select.value ? select.options[select.selectedIndex] : null;
            }

            function refreshStockValidation() {
                const totals = {};
                const rows = Array.from(container.querySelectorAll('.request-line-row'));

                rows.forEach((row) => {
                    const select = row.querySelector('[data-field="item"]');
                    const qty = row.querySelector('[data-field="qty"]');
                    if (!select || !qty || !select.value) return;

                    totals[select.value] = (totals[select.value] || 0) + (parseInt(qty.value || '0', 10) || 0);
                });

                let hasError = false;

                rows.forEach((row) => {
                    const option = getSelectedOption(row);
                    const qty = row.querySelector('[data-field="qty"]');
                    const info = row.querySelector('[data-field="stock-info"]');
                    const error = row.querySelector('[data-field="stock-error"]');

                    if (!qty || !info || !error) return;

                    qty.classList.remove('is-invalid');
                    error.classList.add('d-none');
                    error.textContent = '';

                    if (!option) {
                        qty.removeAttribute('max');
                        info.textContent = '';
                        return;
                    }

                    const stock = parseInt(option.dataset.stock || '0', 10) || 0;
                    const unit = option.dataset.unit || '';
                    const total = totals[option.value] || 0;

                    qty.max = stock;
                    info.textContent = `Stok tersedia: ${new Intl.NumberFormat('id-ID').format(stock)} ${unit}`;

                    if (stock <= 0) {
                        hasError = true;
                        qty.classList.add('is-invalid');
                        error.textContent = 'Habis.';
                        error.classList.remove('d-none');
                    } else if (total > stock) {
                        hasError = true;
                        qty.classList.add('is-invalid');
                        error.textContent = `Total permintaan barang ini ${new Intl.NumberFormat('id-ID').format(total)} ${unit}, melebihi stok ${new Intl.NumberFormat('id-ID').format(stock)} ${unit}.`;
                        error.classList.remove('d-none');
                    }
                });

                if (submitBtn) {
                    submitBtn.disabled = hasError;
                }

                return !hasError;
            }

            function bindLine(row) {
                const select = row.querySelector('[data-field="item"]');
                const qty = row.querySelector('[data-field="qty"]');
                const remove = row.querySelector('.btn-remove-line');

                if (select) select.addEventListener('change', refreshStockValidation);
                if (qty) qty.addEventListener('input', refreshStockValidation);
                if (remove) bindRemove(remove);
            }

            function bindRemove(btn) {
                btn.addEventListener('click', function() {
                    if (container.querySelectorAll('.request-line-row').length <= 1) return;
                    btn.closest('.request-line-row').remove();
                    reindexLineNames();
                    refreshStockValidation();
                });
            }

            container.querySelectorAll('.request-line-row').forEach(bindLine);

            addBtn.addEventListener('click', function() {
                const first = container.querySelector('.request-line-row');
                if (!first) return;
                const row = first.cloneNode(true);
                const sel = row.querySelector('[data-field="item"]');
                const qty = row.querySelector('[data-field="qty"]');
                if (sel) sel.value = '';
                if (qty) qty.value = 1;
                container.appendChild(row);
                bindLine(row);
                reindexLineNames();
                refreshStockValidation();
            });

            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!refreshStockValidation()) {
                        event.preventDefault();
                        Toast.fire({
                            icon: 'error',
                            title: 'Jumlah permintaan melebihi stok tersedia.'
                        });
                    }
                });
            }

            refreshStockValidation();
        })();

        // SweetAlert2 Toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: {!! json_encode(session('success')) !!},
                confirmButtonText: 'OK',
                customClass: {
                    popup: document.documentElement.getAttribute('data-theme') === 'dark' ? 'swal-dark' : '',
                    confirmButton: 'swal-btn-confirm'
                },
                buttonsStyling: false,
            });
        @endif

        @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: {!! json_encode(session('error')) !!}
            });
        @endif

        // Login Modal AJAX
        document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const loginModal = document.getElementById('loginModal');
    const errorDiv = document.getElementById('loginError');
    const errorMsg = document.getElementById('loginErrorMsg');
    const loading = document.getElementById('loginLoading');
    const submitBtn = document.getElementById('loginSubmitBtn');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const showForgotPasswordBtn = document.getElementById('showForgotPasswordBtn');
    const backToLoginBtn = document.getElementById('backToLoginBtn');
    const forgotPasswordSubmitBtn = document.getElementById('forgotPasswordSubmitBtn');
    const invalidLoginMessage = @json(\App\Http\Controllers\LoginController::INVALID_LOGIN_MESSAGE);

    if (!loginForm) return;

    function clearLoginModalState() {
        errorDiv.style.display = 'none';
        errorMsg.innerHTML = '';
        document.querySelectorAll('#loginModal .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    function showLoginForm() {
        clearLoginModalState();
        forgotPasswordForm.classList.add('d-none');
        loginForm.classList.remove('d-none');
        if (loginModal && loginModal.classList.contains('show')) {
            document.getElementById('loginUsername').focus();
        }
    }

    function showForgotPasswordForm() {
        clearLoginModalState();
        loginForm.classList.add('d-none');
        forgotPasswordForm.classList.remove('d-none');
        if (loginModal && loginModal.classList.contains('show')) {
            document.getElementById('resetEmail').focus();
        }
    }

    showForgotPasswordBtn.addEventListener('click', showForgotPasswordForm);
    backToLoginBtn.addEventListener('click', showLoginForm);

    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearLoginModalState();

        loading.classList.add('show');
        submitBtn.disabled = true;

        try {
            const formData = new FormData(loginForm);
            const csrfToken = formData.get('_token') || document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch(`{{ request()->getBaseUrl() }}/login`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : {};

            loading.classList.remove('show');
            submitBtn.disabled = false;

            if (response.ok && data.success) {
                window.location.href = data.redirect || '/dashboard';
                return;
            }

            let messages = [];

            if (response.status === 419) {
                messages.push(invalidLoginMessage);
            } else if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    messages.push(data.errors[key][0]);

                    const input = loginForm.querySelector(`[name="${key}"]`);
                    if (input) input.classList.add('is-invalid');
                });
            }

            if (data.message && response.status !== 419) {
                const message = String(data.message);
                messages.push(message.toLowerCase().includes('csrf') ? invalidLoginMessage : message);
            }

            errorMsg.innerHTML = messages.length
                ? messages.join('<br>')
                : invalidLoginMessage;

            errorDiv.style.display = 'block';

        } catch (error) {
            loading.classList.remove('show');
            submitBtn.disabled = false;

            errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.style.display = 'block';
        }
    });

    forgotPasswordForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearLoginModalState();
        loading.classList.add('show');
        forgotPasswordSubmitBtn.disabled = true;

        try {
            const formData = new FormData(forgotPasswordForm);
            const csrfToken = formData.get('_token') || document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch(`{{ request()->getBaseUrl() }}/forgot-password`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : {};

            loading.classList.remove('show');
            forgotPasswordSubmitBtn.disabled = false;

            if (response.ok && data.success) {
                Toast.fire({
                    icon: 'success',
                    title: data.message || 'Link reset password sudah dikirim.'
                });
                forgotPasswordForm.reset();
                showLoginForm();
                return;
            }

            let messages = [];
            if (data.errors) {
                Object.keys(data.errors).forEach(key => {
                    messages.push(data.errors[key][0]);
                    const input = forgotPasswordForm.querySelector(`[name="${key}"]`);
                    if (input) input.classList.add('is-invalid');
                });
            }

            if (data.message) messages.push(data.message);

            errorMsg.innerHTML = messages.length ? messages.join('<br>') : 'Email tidak dapat diproses.';
            errorDiv.style.display = 'block';
        } catch (error) {
            loading.classList.remove('show');
            forgotPasswordSubmitBtn.disabled = false;
            errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
            errorDiv.style.display = 'block';
        }
    });

    if (loginModal) {
        loginModal.addEventListener('hidden.bs.modal', function () {
            loginForm.reset();
            forgotPasswordForm.reset();
            showLoginForm();
        });
    }
});

        // Reset login form when modal closes
        document.getElementById('loginModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('loginForm').reset();
            document.getElementById('forgotPasswordForm').reset();
            document.getElementById('forgotPasswordForm').classList.add('d-none');
            document.getElementById('loginForm').classList.remove('d-none');
            document.getElementById('loginError').style.display = 'none';
            document.querySelectorAll('#loginModal .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        });
        
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('loginPassword');
            const eyeIcon = document.getElementById('eyeIcon');
            
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeIcon.classList.toggle('bi-eye');
                eyeIcon.classList.toggle('bi-eye-slash');
            });
        });

        // Auto-open login modal if redirected from /login
        @if(session('openLogin'))
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        @endif
    </script>
</body>
</html>
