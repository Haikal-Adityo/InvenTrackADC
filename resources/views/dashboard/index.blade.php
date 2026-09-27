@extends('layouts.app')
@php $isTeknik = isset($saBidang) ? $saBidang === 'teknik' : auth()->user()->bidang === 'teknik'; @endphp
@section('title', $isTeknik ? 'Overview' : 'Dashboard')
@section('subtitle', 'Ringkasan data Barang')

@section('content')
    @php
        $isTeknik = isset($saBidang) ? $saBidang === 'teknik' : auth()->user()->bidang === 'teknik';
        $receiptLabel = $isTeknik ? 'Goods Receipt' : 'Barang Masuk';
        $issueLabel = $isTeknik ? 'Goods Issue' : 'Barang Keluar';
        $componentLabel = $isTeknik ? 'Komponen' : 'Kategori';
        $distributionLabel = $isTeknik ? 'Ship Unloader' : $componentLabel;
        $pendingApprovalLabel = auth()->user()->isManager() && $isTeknik
            ? 'Stock Request Menunggu Approval'
            : 'Menunggu Approval';
    @endphp
    <div class="animate-fade-in {{ $isTeknik ? 'technical-dashboard-page' : '' }}">
        <!-- Stats Cards -->
        @if($isTeknik)
            <div class="technical-dashboard-cards mb-4">
                <div class="technical-dashboard-card technical-total-card">
                        <div class="technical-dashboard-card-copy">
                            <div class="technical-dashboard-card-title">Total Spare Parts (SOH)</div>
                            <div class="technical-dashboard-card-value">
                                {{ number_format($totalItems) }} <span>Items</span>
                            </div>
                            <div class="technical-dashboard-card-trend {{ $totalItemsMonthlyChange < 0 ? 'is-down' : 'is-up' }}">
                                <strong>
                                    <i class="bi bi-graph-{{ $totalItemsMonthlyChange < 0 ? 'down' : 'up' }}-arrow"></i>
                                    {{ $totalItemsMonthlyChange > 0 ? '+' : '' }}{{ number_format($totalItemsMonthlyChange, 1) }}%
                                </strong>
                                <span>vs last month</span>
                            </div>
                    </div>
                    <div class="technical-dashboard-card-icon technical-total-icon">
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                </div>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('items.index', ['stock_status' => 'critical']) }}" class="technical-dashboard-card technical-critical-card">
                        <div class="technical-dashboard-card-copy">
                            <div class="technical-dashboard-card-title">CRITICAL STOCK</div>
                            <div class="technical-dashboard-card-value">{{ number_format($criticalStockCount) }} <span>Items</span></div>
                            <div class="technical-dashboard-card-link">View items requiring restock <i class="bi bi-arrow-right"></i></div>
                        </div>
                        <div class="technical-dashboard-card-icon technical-critical-icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </a>
                @else
                    <div class="technical-dashboard-card technical-critical-card">
                        <div class="technical-dashboard-card-copy">
                            <div class="technical-dashboard-card-title">Critical Stock</div>
                            <div class="technical-dashboard-card-value">{{ number_format($criticalStockCount) }} <span>Items</span></div>
                            <div class="technical-dashboard-card-link">Items requiring restock</div>
                        </div>
                        <div class="technical-dashboard-card-icon technical-critical-icon">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                @endif

                <div class="technical-dashboard-card technical-type-card">
                    <div class="technical-dashboard-card-copy">
                        <div class="technical-type-card-head">
                            <div>
                                <div class="technical-dashboard-card-title">Total Item Types</div>
                                <div class="technical-dashboard-card-subtitle">Categories registered</div>
                            </div>
                            <div class="technical-type-card-total">{{ number_format($technicalTypeSummary['total_types'] ?? 0) }}</div>
                        </div>
                        <div class="technical-type-card-list">
                            @forelse(array_slice($technicalTypeSummary['top_types'] ?? [], 0, 2) as $type)
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
        @else
            <div class="row g-3 mb-4 dashboard-summary-cards">
                <div class="col-sm-6 col-lg-3">
                    <div class="stats-card primary">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-box-seam-fill"></i>
                            </div>
                            <div class="stats-copy">
                                <div class="stats-value" style="font-size:22px;">{{ number_format($totalItems) }}</div>
                                <div class="stats-label">Total Barang</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stats-card success">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                            </div>
                            <div class="stats-copy">
                                <div class="stats-value" style="font-size:22px;">{{ number_format($masukBulanIni) }}</div>
                                <div class="stats-label">{{ $receiptLabel }} (Bulan Ini)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stats-card danger">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                            </div>
                            <div class="stats-copy">
                                <div class="stats-value" style="font-size:22px;">{{ number_format($keluarBulanIni) }}</div>
                                <div class="stats-label">{{ $issueLabel }} (Bulan Ini)</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="stats-card warning">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stats-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stats-copy">
                                <div class="stats-value" style="font-size:22px;">{{ number_format($pendingCount) }}</div>
                                <div class="stats-label">{{ $pendingApprovalLabel }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ADMIN: Pending Transactions Approval (Per Hari) --}}
        @if(!$isTeknik && auth()->user()->isAdmin() && $pendingByDate->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <span><i class="bi bi-check2-square text-warning-custom me-2"></i>Transaksi Menunggu Approval</span>
                    <span class="badge bg-warning text-dark">{{ $pendingCount }} pending</span>
                </div>
                <div class="card-body p-0">
                    @foreach($pendingByDate as $date => $transactions)
                        <div class="approval-date-group">
                            <div class="d-flex align-items-center justify-content-between px-3 py-3"
                                style="background:var(--body-bg); border-bottom:1px solid var(--border-color);">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-calendar3 text-primary-custom"></i>
                                    <span class="fw-700"
                                        style="font-size:14px;">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
                                    <span class="badge bg-secondary" style="font-size:10px;">{{ $transactions->count() }}
                                        transaksi</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <form action="{{ route('dashboard.approveByDate') }}" method="POST" style="display:inline;"
                                        id="approveDate-{{ $loop->index }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <button type="button" class="btn btn-success btn-sm"
                                            onclick="swalConfirm('Approve Semua', 'Approve semua transaksi tanggal ini?', 'question', 'Ya, Approve', '#approveDate-{{ $loop->index }}')">
                                            <i class="bi bi-check-all"></i> Approve Semua
                                        </button>
                                    </form>
                                    <form action="{{ route('dashboard.rejectByDate') }}" method="POST" style="display:inline;"
                                        id="rejectDate-{{ $loop->index }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ $date }}">
                                        <button type="button" class="btn btn-danger btn-sm"
                                            onclick="swalConfirm('Reject Semua', 'Reject semua transaksi tanggal ini?', 'warning', 'Ya, Reject', '#rejectDate-{{ $loop->index }}')">
                                            <i class="bi bi-x-lg"></i> Reject Semua
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="table-container">
                                <table class="table" style="margin-bottom:0;">
                                    <thead>
                                        <tr>
                                            <th class="col-name-wrap">Barang</th>
                                            <th>{{ $componentLabel }}</th>
                                            <th>Jenis</th>
                                            <th>Jumlah</th>
                                            <th>Satuan</th>
                                            @unless($isTeknik)
                                                <th>Harga Satuan</th>
                                            @endunless
                                            <th>User</th>
                                            <th>Keterangan</th>
                                            <th class="text-end" style="white-space:nowrap;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $tx)
                                            <tr>
                                                <td class="fw-600 col-name-wrap">{{ $tx->item->name ?? 'Barang dihapus' }}</td>
                                                <td>{{ $isTeknik ? ($tx->item->component ?? '-') : ($tx->item->category ?? '-') }}</td>
                                                <td>
                                                    <span class="badge-status badge-{{ $tx->type }}">
                                                        <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"
                                                            style="font-size:10px;"></i>
                                                        {{ $tx->type_label }}
                                                    </span>
                                                </td>
                                                <td class="fw-700">{{ number_format($tx->quantity) }}</td>
                                                <td>{{ $tx->item->unit ?? '-' }}</td>
                                                @unless($isTeknik)
                                                    <td>{{ $tx->price === null ? '-' : 'Rp ' . number_format($tx->price, 0, ',', '.') }}</td>
                                                @endunless
                                                <td>{{ $tx->user->name ?? '-' }}</td>
                                                <td style="font-size:12px; color:var(--text-secondary);">
                                                    {{ Str::limit($tx->description, 40) }}
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2"
                                                            title="Edit transaksi" onclick="openTxEditModal({{ $tx->id }})">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </button>
                                                        <form action="{{ route('transactions.destroy', $tx) }}" method="POST"
                                                            class="d-inline" id="deleteTx-{{ $tx->id }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                                                title="Hapus transaksi"
                                                                onclick="swalConfirm('Hapus Transaksi', 'Hapus transaksi pending ini?', 'warning', 'Ya, Hapus', '#deleteTx-{{ $tx->id }}')">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @unless(auth()->user()->bidang === 'teknik')
        <!-- Search Bar (Autocomplete) -->
        <div class="card mb-4" id="searchCard">
            <div class="card-body py-3">
                <div class="position-relative" id="searchWrapper">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-search" style="color:var(--text-secondary); font-size:18px;"></i>
                        <input type="text" id="dashboardSearchInput" class="form-control"
                            placeholder="Cari barang untuk filter grafik..." autocomplete="off"
                            style="background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); padding:10px 14px; border-radius:10px; font-size:14px;">
                        <button type="button" id="clearSearchBtn" class="btn btn-sm btn-outline-secondary"
                            style="display:none; border-radius:8px; white-space:nowrap;">
                            <i class="bi bi-x-lg"></i> Reset
                        </button>
                    </div>
                    <div id="searchSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
                    <div id="activeFilter" style="display:none; margin-top:10px;">
                        <span class="badge"
                            style="background:var(--primary-bg); color:var(--primary); padding:6px 14px; font-size:12px; border-radius:8px;">
                            <i class="bi bi-funnel-fill me-1"></i>
                            Filter aktif: <strong id="activeFilterName"></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endunless

        <!-- Charts Row: Monthly + Category -->
        <div class="row g-3 mb-4 public-technical-chart-row">
            <div class="{{ $isTeknik ? 'col-lg-8' : 'col-lg-8' }}">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        @if($isTeknik)
                            <div>
                                <h5 class="mb-0 fw-bold">Movement Analysis</h5>
                                <small class="text-secondary" style="font-size: 11px;">GR vs GI tracking trend</small>
                            </div>
                        @else
                            <span>
                                <i class="bi bi-graph-up text-primary-custom me-2"></i>
                                {{ $receiptLabel }} vs {{ $issueLabel }} (12 Bulan)
                            </span>
                        @endif

                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            @if($isTeknik)
                                <select id="monthlyPeriodFilter" onchange="changeMonthlyChart()" class="form-select form-select-sm"
                                    style="width:auto; min-width:140px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                                    <option value="thisMonth" {{ $selectedMonthlyPeriod === 'thisMonth' ? 'selected' : '' }}>Bulan Ini</option>
                                    <option value="6months" {{ $selectedMonthlyPeriod === '6months' ? 'selected' : '' }}>6 Bulan Terakhir</option>
                                    <option value="ytd" {{ $selectedMonthlyPeriod === 'ytd' ? 'selected' : '' }}>12 Bulan</option>
                                </select>
                            @endif
                            <select id="monthlyYearFilter" onchange="changeMonthlyChart()" class="form-select form-select-sm"
                                style="width:auto; min-width:120px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="chart-container" style="height:320px;">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        @if($isTeknik)
                            <div>
                                <h5 class="mb-0 fw-bold">Asset Distribution</h5>
                                <small class="text-secondary" style="font-size: 11px;">By Ship Unloader Unit</small>
                            </div>
                        @else
                            <span><i class="bi bi-pie-chart-fill text-primary-custom me-2"></i>Stok per {{ $distributionLabel }}</span>
                            <select id="categoryYearFilter" onchange="changeCategoryChart()" class="form-select form-select-sm"
                                style="width:auto; min-width:120px; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px; padding:4px 28px 4px 10px;">
                                <option value="">Semua Tahun</option>
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height:320px;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Yearly Chart Row -->
        <div class="row g-3 mb-4 public-technical-chart-row">
            <div class="{{ $isTeknik ? 'col-lg-12' : 'col-lg-8' }}">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        @if($isTeknik)
                            <div>
                                <h5 class="mb-0 fw-bold">Movement Analysis</h5>
                                <small class="text-secondary" style="font-size: 11px;">GR vs GI tracking trend</small>
                            </div>
                        @else
                            <span>
                                <i class="bi bi-activity text-primary-custom me-2"></i>
                                {{ $receiptLabel }} vs {{ $issueLabel }} (Tahunan)
                            </span>
                        @endif

                        <div class="d-flex align-items-center gap-2">
                            <select onchange="changeYearRange()" id="startYear" class="form-select form-select-sm"
                                style="width:auto; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $startYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>

                            <span class="text-muted" style="font-size:12px;">-</span>

                            <select onchange="changeYearRange()" id="endYear" class="form-select form-select-sm"
                                style="width:auto; background:var(--body-bg); border:1px solid var(--border-color); color:var(--text-color); border-radius:8px; font-size:12px;">
                                @foreach($availableYears as $year)
                                    <option value="{{ $year }}" {{ $endYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="chart-container" style="height:320px;">
                            <canvas id="yearlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isTeknik)
            <div class="row g-3 mb-4 technical-soh-activity-row">
                <div class="col-lg-8">
                    <div class="card technical-soh-detail-card">
                        <div class="card-header">
                            <span><i class="bi bi-table text-primary-custom me-2"></i>Detailed Stock On Hand Table</span>
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
                                        @forelse($detailedSohTransactions as $tx)
                                            <tr>
                                                <td class="text-secondary">{{ $tx->date ? $tx->date->format('Y-m-d') : ($tx->created_at ? $tx->created_at->format('Y-m-d') : '-') }}</td>
                                                <td>
                                                    <span class="{{ $tx->type === 'in' ? 'text-in' : 'text-out' }} fw-bold" style="font-family: 'Roboto Mono', monospace; font-size:13px;">
                                                        {{ $tx->item?->no_normalisasi ?: 'NORM-' . str_pad($tx->item_id, 4, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                </td>
                                                <td class="fw-medium col-name-wrap">{{ $tx->item?->name ?? 'Barang dihapus' }}</td>
                                                <td>
                                                    <div class="d-flex flex-column">
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
                            <span><i class="bi bi-clock-fill text-primary-custom me-2"></i>Recent Activity Feed</span>
                            @if(auth()->user()->isAdmin() && auth()->user()->bidang !== 'teknik')
                                <form action="{{ route('sync.sheets') }}" method="POST" style="display:inline;" id="syncSheetsForm">
                                    @csrf
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="swalConfirm('Sync Data', 'Sync semua data ke Google Sheets?', 'question', 'Ya, Sync', '#syncSheetsForm')"
                                        title="Sync ke Google Sheets">
                                        <i class="bi bi-cloud-arrow-up-fill"></i> Sync
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="card-body technical-activity-feed">
                            @forelse($recentTransactions as $tx)
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

        @unless($isTeknik)
            <!-- Bottom Row -->
            <div class="row g-3">
            <!-- Low Stock Alert -->
            <div class="col-lg-4">
                <div class="card low-stock-card">
                    <div class="card-header">
                        <span><i class="bi bi-exclamation-triangle-fill text-warning-custom me-2"></i>Stok Menipis</span>
                        @if($lowStockItems->count() > 0)
                            <span class="badge bg-danger">{{ $lowStockItems->count() }}</span>
                        @endif
                    </div>
                    <div class="card-body" style="max-height:350px; overflow-y:auto;">
                        @forelse($lowStockItems as $item)
                                @php
                                    $totalMasuk = $item->transactions()->masuk()->approved()->sum('quantity');
                                    $totalKeluar = $item->transactions()->keluar()->approved()->sum('quantity');
                                    $currentStock = $totalMasuk - $totalKeluar;
                                @endphp
                            <div class="low-stock-item">
                                <div class="item-info">
                                    <h6>{{ $item->name }}</h6>
                                    <span>{{ $item->category }} · Min: {{ $item->min_stock }} {{ $item->unit }}</span>
                                </div>

                                @if($currentStock <= 0)
                                    <div class="text-danger fw-600">{{ number_format($currentStock) }}</div>
                                @elseif($currentStock <= $item->min_stock)
                                    <div class="fw-600" style="color: var(--warning-dark);">{{ number_format($currentStock) }}</div>
                                @else
                                    <div class="text-success-custom fw-600">{{ number_format($currentStock) }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state" style="padding:30px 10px;">
                                <i class="bi bi-check-circle" style="font-size:40px; color:var(--success);"></i>
                                <h6 class="mt-2" style="font-size:13px;">Semua stok aman</h6>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Top Items Keluar -->
            <div class="col-lg-4">
                <div class="card item-keluar">
                    <div class="card-header">
                        <span><i class="bi bi-fire text-danger-custom me-2"></i>{{ $isTeknik ? 'Goods Issue Terbanyak' : 'Barang Paling Sering Keluar' }}</span>
                    </div>
                    <div class="card-body">
                        @forelse($topKeluar as $index => $tx)
                            <div
                                class="d-flex align-items-center justify-content-between py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fw-700"
                                        style="width:24px;height:24px;border-radius:50%;background:var(--primary-bg);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:11px;">{{ $index + 1 }}</span>
                                    <div>
                                        <div class="fw-600" style="font-size:13px;">{{ $tx->item->name ?? 'Barang dihapus' }}
                                        </div>
                                        <div style="font-size:11px;color:var(--text-secondary);">
                                            @if($isTeknik && $tx->item?->no_normalisasi)
                                                {{ $tx->item->no_normalisasi }} -
                                            @endif
                                            {{ $tx->item->category ?? '' }}
                                        </div>
                                    </div>
                                </div>
                                <span class="fw-700" style="color: #CA5995;">{{ number_format($tx->total) }}</span>
                            </div>
                        @empty
                            <div class="empty-state" style="padding:30px 10px;">
                                <i class="bi bi-inbox" style="font-size:40px;"></i>
                                <h6 class="mt-2" style="font-size:13px;">Belum ada data</h6>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Transactions + Sync Button -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-clock-fill text-primary-custom me-2"></i>Transaksi Terbaru</span>
                        @if(auth()->user()->isAdmin())
                            <form action="{{ route('sync.sheets') }}" method="POST" style="display:inline;" id="syncSheetsForm">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="swalConfirm('Sync Data', 'Sync semua data ke Google Sheets?', 'question', 'Ya, Sync', '#syncSheetsForm')"
                                    title="Sync ke Google Sheets">
                                    <i class="bi bi-cloud-arrow-up-fill"></i> Sync
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="card-body" style="max-height:350px; overflow-y:auto;">
                        @forelse($recentTransactions as $tx)
                            <div class="d-flex align-items-start gap-3 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div
                                    style="width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;{{ $tx->type === 'in' ? 'background:var(--success-bg);color:var(--success);' : 'background:var(--danger-bg);color:var(--danger);' }}">
                                    <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="fw-600" style="font-size:13px;">{{ $tx->item->name ?? 'Barang dihapus' }}</div>
                                    <div style="font-size:11px;color:var(--text-secondary);">
                                        {{ $tx->type_label }} - {{ $tx->quantity }} {{ $tx->item->unit ?? '' }} - {{ $tx->user->name ?? '' }}
                                    </div>
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
        @endunless
    </div>

    <style>
        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
            max-height: 300px;
            overflow-y: auto;
            margin-top: -2px;
        }

        .autocomplete-item {
            padding: 10px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.15s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover,
        .autocomplete-item.active {
            background: var(--primary-bg);
        }

        .autocomplete-item .item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--primary-bg);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .autocomplete-item .item-name {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-color);
        }

        .autocomplete-item .item-category {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .autocomplete-no-result {
            padding: 16px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
        }
    </style>

    {{-- Transaction Edit Modal --}}
    @if(auth()->user()->isAdmin())
        <div class="modal fade inventrack-modal {{ $isTeknik ? 'technical-transaction-modal' : '' }}" id="dashTxModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="position:relative;">
                    <div class="modal-loading-overlay" id="dashTxLoading">
                        <div class="modal-spinner"></div>
                    </div>
                    <div class="modal-header">
                        <h5 class="modal-title" id="dashTxModalTitle">
                            <i class="bi bi-pencil-fill"></i> <span>Edit Transaksi</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="modal-error-alert" id="dashTxError">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            <span id="dashTxErrorMsg"></span>
                        </div>
                        <form id="dashTxForm" novalidate>
                            <input type="hidden" id="dashTxId" value="">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="date" class="form-control" required id="dashTxDate">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                                    <select name="type" class="form-select" required id="dashTxType">
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="in">{{ $isTeknik ? 'Goods Receipt (IN)' : 'Barang Masuk' }}</option>
                                        <option value="out">{{ $isTeknik ? 'Goods Issue (OUT)' : 'Barang Keluar' }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nama Barang <span class="text-danger">*</span></label>
                                <select name="item_id" class="form-select" required id="dashTxItemSelect">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach($items as $item)
                                        <option value="{{ $item->id }}" data-category="{{ $item->category }}" data-component="{{ $item->component }}"
                                            data-unit="{{ $item->unit }}" data-stock="{{ $item->current_stock }}">
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ $componentLabel }}</label>
                                    <input type="text" class="form-control" id="dashTxItemCategory" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Satuan</label>
                                    <input type="text" class="form-control" id="dashTxItemUnit" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Stok Saat Ini</label>
                                    <input type="text" class="form-control" id="dashTxItemStock" readonly>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" class="form-control" min="1" placeholder="Masukkan jumlah"
                                    required id="dashTxQuantity">
                                <div id="dashTxStockWarning" class="text-danger mt-1" style="font-size:12px;display:none;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Jumlah melebihi stok yang tersedia!
                                </div>
                            </div>

                            @unless($isTeknik)
                                <div class="mb-3">
                                    <label class="form-label">Harga Satuan</label>
                                    <input type="number" name="price" class="form-control" min="0" step="1"
                                        placeholder="Kosongkan jika tidak ada" id="dashTxPrice">
                                </div>
                            @endunless

                            <div class="mb-3">
                                <label class="form-label">Keterangan</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Keterangan tambahan (opsional)" id="dashTxDescription"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="dashTxSubmitBtn" onclick="submitDashTxForm()">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
    (function () {
        // ===== Super Admin bidang context for AJAX =====
        const saBidangParam = @json($saBidang ?? null) ? `&sa_bidang={{ $saBidang ?? '' }}` : '';

        // ===== Theme detection =====
        const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
        const chartTextColor = () => isDark() ? '#cbd5e1' : '#475569';
        const chartMutedColor = () => isDark() ? '#64748b' : '#94a3b8';
        const chartGridColor = () => isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        const chartSurfaceColor = () => isDark() ? 'rgba(15,23,42,0.95)' : 'rgba(255,255,255,0.95)';
        const chartCssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

        function updateChartTheme() {
            [monthlyChartInstance, yearlyChartInstance, categoryChartInstance].forEach(chart => {
                if (chart.options.plugins?.legend?.labels) {
                    chart.options.plugins.legend.labels.color = chartTextColor();
                }

                if (chart.options.plugins?.tooltip) {
                    chart.options.plugins.tooltip.backgroundColor = chartSurfaceColor();
                    chart.options.plugins.tooltip.titleColor = isDark() ? '#f8fafc' : '#0f172a';
                    chart.options.plugins.tooltip.bodyColor = chartTextColor();
                    chart.options.plugins.tooltip.borderColor = isDark() ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.1)';
                }

                if (chart.options.scales) {
                    if (chart.options.scales.x?.ticks) {
                        chart.options.scales.x.ticks.color = chartMutedColor();
                    }

                    if (chart.options.scales.y?.ticks) {
                        chart.options.scales.y.ticks.color = chartMutedColor();
                    }

                    if (chart.options.scales.y?.grid) {
                        chart.options.scales.y.grid.color = chartGridColor();
                    }
                }

                chart.update();
            });

            monthlyChartInstance.data.datasets[0].backgroundColor = lineGradient(monthlyCtx, 'gr');
            monthlyChartInstance.data.datasets[1].backgroundColor = lineGradient(monthlyCtx, 'gi');
            yearlyChartInstance.data.datasets[0].backgroundColor = lineGradient(yearlyCtx, 'gr');
            yearlyChartInstance.data.datasets[1].backgroundColor = lineGradient(yearlyCtx, 'gi');
            [monthlyChartInstance, yearlyChartInstance].forEach(chart => {
                chart.data.datasets.forEach(dataset => {
                    dataset.pointBackgroundColor = chartCssVar('--chart-point-bg');
                    dataset.pointHoverBackgroundColor = chartCssVar('--chart-point-hover-bg');
                });
                chart.update();
            });

            categoryChartInstance.data.datasets[0].backgroundColor = categoryChartInstance.data.labels
                .map((cat, idx) => donutGradient(catCtx, getCategoryColor(cat, idx)));
            categoryChartInstance.data.datasets[0].borderColor = categoryChartInstance.data.labels
                .map((cat, idx) => getCategoryColor(cat, idx));
            categoryChartInstance.update();
        }

        function changeMonthlyChart() {
            const year = document.getElementById('monthlyYearFilter')?.value || @json($selectedYear);
            const period = document.getElementById('monthlyPeriodFilter')?.value || 'ytd';
            fetch(`{{ request()->getBaseUrl() }}/dashboard/api/monthly-data?year=${encodeURIComponent(year)}&period=${encodeURIComponent(period)}${saBidangParam}`)
                .then(res => res.json())
                .then(data => {

                    monthlyChartInstance.data.labels = data.map(d => d.label);
                    monthlyChartInstance.data.datasets[0].data = data.map(d => d.masuk);
                    monthlyChartInstance.data.datasets[1].data = data.map(d => d.keluar);

                    monthlyChartInstance.update('active');
                })
                .catch(err => console.error(err));
        }

        function changeYearRange() {
            const start = document.getElementById('startYear').value;
            const end = document.getElementById('endYear').value;

            const url = new URL(window.location.href);

            url.searchParams.set('start_year', start);
            url.searchParams.set('end_year', end);

            window.location.href = url.toString();
        }

        // ===== Chart color constants =====
        const masukBorder = '#3b82f6';
        const keluarBorder = '#10b981';
        const catColors = ['#da3e23ff', '#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#06b6d4', '#ef4444', '#14b8a6', '#ec4899'];

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

        function donutGradient(ctx, color) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, hexToRgba(color, chartCssVar('--chart-donut-alpha-start')));
            gradient.addColorStop(1, hexToRgba(color, chartCssVar('--chart-donut-alpha-end')));
            return gradient;
        }

        function hexToRgba(hex, alpha) {
            const value = hex.replace('#', '');
            const r = parseInt(value.substring(0, 2), 16);
            const g = parseInt(value.substring(2, 4), 16);
            const b = parseInt(value.substring(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
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

        // ===== Common chart options factory =====
        function premiumLineOptions() {
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

        // ===== Monthly Chart =====
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChartInstance = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($monthlyData, 'label')) !!},
                datasets: [
                    {
                        label: @json($receiptLabel),
                        data: {!! json_encode(array_column($monthlyData, 'masuk')) !!},
                        fill: true,
                        backgroundColor: lineGradient(monthlyCtx, 'gr'),
                        borderColor: masukBorder,
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: chartCssVar('--chart-point-bg'),
                        pointBorderColor: masukBorder,
                        pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                        pointHoverBorderColor: masukBorder,
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    },
                    {
                        label: @json($issueLabel),
                        data: {!! json_encode(array_column($monthlyData, 'keluar')) !!},
                        fill: true,
                        backgroundColor: lineGradient(monthlyCtx, 'gi'),
                        borderColor: keluarBorder,
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: chartCssVar('--chart-point-bg'),
                        pointBorderColor: keluarBorder,
                        pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                        pointHoverBorderColor: keluarBorder,
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }
                ]
            },
            options: premiumLineOptions()
        });

        // ===== Yearly Chart =====
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        const yearlyChartInstance = new Chart(yearlyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($yearlyData, 'label')) !!},
                datasets: [
                    {
                        label: @json($receiptLabel),
                        data: {!! json_encode(array_column($yearlyData, 'masuk')) !!},
                        fill: true,
                        backgroundColor: lineGradient(yearlyCtx, 'gr'),
                        borderColor: masukBorder,
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: chartCssVar('--chart-point-bg'),
                        pointBorderColor: masukBorder,
                        pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                        pointHoverBorderColor: masukBorder,
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    },
                    {
                        label: @json($issueLabel),
                        data: {!! json_encode(array_column($yearlyData, 'keluar')) !!},
                        fill: true,
                        backgroundColor: lineGradient(yearlyCtx, 'gi'),
                        borderColor: keluarBorder,
                        borderWidth: 2,
                        tension: 0.4,
                        pointBackgroundColor: chartCssVar('--chart-point-bg'),
                        pointBorderColor: keluarBorder,
                        pointHoverBackgroundColor: chartCssVar('--chart-point-hover-bg'),
                        pointHoverBorderColor: keluarBorder,
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }
                ]
            },
            options: premiumLineOptions()
        });

        // ===== Category Donut Chart =====
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        const initialCatLabels = {!! json_encode(array_column($categoryData, 'category')) !!};
        const categoryChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: initialCatLabels,
                datasets: [{
                    data: {!! json_encode(array_column($categoryData, 'stock')) !!},
                    backgroundColor: initialCatLabels.map((cat, idx) => donutGradient(catCtx, getCategoryColor(cat, idx))),
                    borderColor: initialCatLabels.map((cat, idx) => getCategoryColor(cat, idx)),
                    borderWidth: 2,
                    hoverOffset: 6,
                    spacing: 4,
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

        // ===== Autocomplete Search =====
        const searchInput = document.getElementById('dashboardSearchInput');
        const suggestionsBox = document.getElementById('searchSuggestions');
        const clearBtn = document.getElementById('clearSearchBtn');
        const activeFilter = document.getElementById('activeFilter');
        const activeFilterName = document.getElementById('activeFilterName');
        let debounceTimer = null;
        let selectedItemId = null;
        let activeIndex = -1;

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const q = this.value.trim();
                clearTimeout(debounceTimer);

                if (q.length < 1) {
                    suggestionsBox.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`{{ request()->getBaseUrl() }}/dashboard/api/search-items?q=${encodeURIComponent(q)}${saBidangParam}`)
                        .then(res => res.json())
                        .then(items => {
                            activeIndex = -1;
                            if (items.length === 0) {
                                suggestionsBox.innerHTML = '<div class="autocomplete-no-result"><i class="bi bi-search me-1"></i>Tidak ada barang ditemukan</div>';
                            } else {
                                suggestionsBox.innerHTML = items.map((item, idx) => `
                                    <div class="autocomplete-item"
                                        data-id="${item.id}"
                                        data-name="${item.name}"
                                        data-index="${idx}">
                                        
                                        <div class="item-icon">
                                            <i class="bi bi-box-seam"></i>
                                        </div>

                                        <div>
                                            <div class="item-name">
                                                ${highlightMatch(item.name, q)}
                                            </div>

                                            <div class="item-category">
                                                ${item.no_normalisasi ? item.no_normalisasi + ' · ' : ''}
                                                ${item.category || ''}
                                            </div>
                                        </div>
                                    </div>
                                `).join('');

                                // Event klik pada item autocomplete
                                suggestionsBox.querySelectorAll('.autocomplete-item').forEach(itemEl => {
                                    itemEl.addEventListener('click', function () {
                                        selectItem(this.dataset.id, this.dataset.name);
                                    });
                                });
                            }
                            suggestionsBox.style.display = 'block';
                        })
                        .catch(() => {
                            suggestionsBox.style.display = 'none';
                        });
                }, 250);
            });

            // Keyboard navigation
            searchInput.addEventListener('keydown', function (e) {
                const items = suggestionsBox.querySelectorAll('.autocomplete-item');
                if (!items.length) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIndex = Math.min(activeIndex + 1, items.length - 1);
                    updateActiveItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIndex = Math.max(activeIndex - 1, 0);
                    updateActiveItem(items);
                } else if (e.key === 'Enter' && activeIndex >= 0) {
                    e.preventDefault();
                    const active = items[activeIndex];
                    selectItem(active.dataset.id, active.dataset.name);
                } else if (e.key === 'Escape') {
                    suggestionsBox.style.display = 'none';
                }
            });

            function updateActiveItem(items) {
                items.forEach((el, i) => el.classList.toggle('active', i === activeIndex));
                if (items[activeIndex]) {
                    items[activeIndex].scrollIntoView({ block: 'nearest' });
                }
            }

            function highlightMatch(text, query) {
                const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                return text.replace(regex, '<mark style="background:var(--primary-bg);color:var(--primary);padding:0 2px;border-radius:3px;">$1</mark>');
            }

            function selectItem(itemId, itemName) {
                selectedItemId = itemId;
                searchInput.value = itemName;
                suggestionsBox.style.display = 'none';
                clearBtn.style.display = 'inline-flex';
                activeFilter.style.display = 'block';
                activeFilterName.textContent = itemName;

                // Fetch filtered chart data
                fetch(`{{ request()->getBaseUrl() }}/dashboard/api/chart-data?item_id=${itemId}${saBidangParam}`)
                    .then(res => res.json())
                    .then(data => {
                        // Update monthly chart
                        monthlyChartInstance.data.labels = data.monthlyData.map(d => d.label);
                        monthlyChartInstance.data.datasets[0].data = data.monthlyData.map(d => d.masuk);
                        monthlyChartInstance.data.datasets[1].data = data.monthlyData.map(d => d.keluar);
                        monthlyChartInstance.update('active');

                        // Update yearly chart
                        yearlyChartInstance.data.labels = data.yearlyData.map(d => d.label);
                        yearlyChartInstance.data.datasets[0].data = data.yearlyData.map(d => d.masuk);
                        yearlyChartInstance.data.datasets[1].data = data.yearlyData.map(d => d.keluar);
                        yearlyChartInstance.update('active');
                    });
            }

            // Clear / reset search
            clearBtn.addEventListener('click', function () {
                selectedItemId = null;
                searchInput.value = '';
                suggestionsBox.style.display = 'none';
                clearBtn.style.display = 'none';
                activeFilter.style.display = 'none';

                // Reset charts to original data
                fetch(`{{ request()->getBaseUrl() }}/dashboard/api/chart-data?_=1${saBidangParam}`)
                    .then(res => res.json())
                    .then(data => {
                        monthlyChartInstance.data.labels = data.monthlyData.map(d => d.label);
                        monthlyChartInstance.data.datasets[0].data = data.monthlyData.map(d => d.masuk);
                        monthlyChartInstance.data.datasets[1].data = data.monthlyData.map(d => d.keluar);
                        monthlyChartInstance.update('active');

                        yearlyChartInstance.data.labels = data.yearlyData.map(d => d.label);
                        yearlyChartInstance.data.datasets[0].data = data.yearlyData.map(d => d.masuk);
                        yearlyChartInstance.data.datasets[1].data = data.yearlyData.map(d => d.keluar);
                        yearlyChartInstance.update('active');
                    });
            });

            // Close suggestions when clicking outside
            document.addEventListener('click', function (e) {
                const searchWrapper = document.getElementById('searchWrapper');
                if (searchWrapper && !searchWrapper.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });
        }

        // ===== Category Year Filter =====
        document.getElementById('categoryYearFilter')?.addEventListener('change', function () {
            const year = this.value;
            const url = year
                ? `{{ request()->getBaseUrl() }}/dashboard/api/category-by-year?year=${year}${saBidangParam}`
                : `{{ request()->getBaseUrl() }}/dashboard/api/category-by-year?_=1${saBidangParam}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    categoryChartInstance.data.labels = data.map(d => d.category);
                    categoryChartInstance.data.datasets[0].data = data.map(d => d.stock);
                    categoryChartInstance.data.datasets[0].backgroundColor = data.map((d, idx) => donutGradient(catCtx, getCategoryColor(d.category, idx)));
                    categoryChartInstance.data.datasets[0].borderColor = data.map((d, idx) => getCategoryColor(d.category, idx));
                    categoryChartInstance.update('active');
                });
        });

        // ===== Transaction Edit Modal (Dashboard) =====
        @if(auth()->user()->isAdmin())
            const dashTxModalEl = document.getElementById('dashTxModal');
            const dashTxModal = dashTxModalEl ? new bootstrap.Modal(dashTxModalEl) : null;

            // Item select -> auto-fill
            const dashTxItemSelect = document.getElementById('dashTxItemSelect');
            const dashTxCategoryInput = document.getElementById('dashTxItemCategory');
            const dashTxUnitInput = document.getElementById('dashTxItemUnit');
            const dashTxStockInput = document.getElementById('dashTxItemStock');
            const dashTxQuantityInput = document.getElementById('dashTxQuantity');
            const dashTxPriceInput = document.getElementById('dashTxPrice');
            const dashTxTypeSelect = document.getElementById('dashTxType');
            const dashTxStockWarning = document.getElementById('dashTxStockWarning');

            if (dashTxItemSelect) {
                dashTxItemSelect.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    if (this.value) {
                dashTxCategoryInput.value = @json($isTeknik) ? (selected.dataset.component || '') : (selected.dataset.category || '');
                        dashTxUnitInput.value = selected.dataset.unit || '';
                        dashTxStockInput.value = selected.dataset.stock || '0';
                        if (dashTxPriceInput && document.getElementById('dashTxType').value === 'out') {
                            dashTxPriceInput.value = '';
                        }
                    } else {
                        dashTxCategoryInput.value = '';
                        dashTxUnitInput.value = '';
                        dashTxStockInput.value = '';
                    }
                    checkDashTxStock();
                });

                dashTxQuantityInput.addEventListener('input', checkDashTxStock);
                dashTxTypeSelect.addEventListener('change', checkDashTxStock);
            }

            function checkDashTxStock() {
                if (dashTxPriceInput) {
                    const disabled = dashTxTypeSelect.value === 'out';
                    if (disabled) dashTxPriceInput.value = '';
                    dashTxPriceInput.disabled = disabled;
                }

                if (dashTxTypeSelect.value === 'out' && dashTxItemSelect.value) {
                    const stock = parseInt(dashTxStockInput.value) || 0;
                    const qty = parseInt(dashTxQuantityInput.value) || 0;
                    dashTxStockWarning.style.display = qty > stock ? 'block' : 'none';
                } else {
                    dashTxStockWarning.style.display = 'none';
                }
            }

            function openTxEditModal(id) {
                // Reset form
                document.getElementById('dashTxForm').reset();
                document.getElementById('dashTxError').style.display = 'none';
                document.querySelectorAll('#dashTxForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
                dashTxCategoryInput.value = '';
                dashTxUnitInput.value = '';
                dashTxStockInput.value = '';
                dashTxStockWarning.style.display = 'none';

                document.getElementById('dashTxId').value = id;

                const loading = document.getElementById('dashTxLoading');
                loading.classList.add('show');
                dashTxModal.show();

                fetch(`{{ request()->getBaseUrl() }}/transactions/${id}/edit-data`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(res => res.json())
                    .then(data => {
                        loading.classList.remove('show');
                        document.getElementById('dashTxDate').value = data.date;
                        document.getElementById('dashTxType').value = data.type;
                        document.getElementById('dashTxItemSelect').value = data.item_id;
                        document.getElementById('dashTxQuantity').value = data.quantity;
                        if (dashTxPriceInput) dashTxPriceInput.value = data.price || '';
                        document.getElementById('dashTxDescription').value = data.description || '';

                        // Fill item info
                        dashTxCategoryInput.value = @json($isTeknik) ? (data.item?.component || '') : (data.item?.category || '');
                        dashTxUnitInput.value = data.item?.unit || '';
                        dashTxStockInput.value = data.item?.current_stock || '0';
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        document.getElementById('dashTxErrorMsg').textContent = 'Gagal memuat data transaksi.';
                        document.getElementById('dashTxError').style.display = 'block';
                    });
            }

            function submitDashTxForm() {
                const form = document.getElementById('dashTxForm');
                const errorDiv = document.getElementById('dashTxError');
                const errorMsg = document.getElementById('dashTxErrorMsg');
                const loading = document.getElementById('dashTxLoading');
                const submitBtn = document.getElementById('dashTxSubmitBtn');
                const txId = document.getElementById('dashTxId').value;

                errorDiv.style.display = 'none';
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                loading.classList.add('show');
                submitBtn.disabled = true;

                const formData = new FormData(form);
                formData.append('_method', 'PUT');

                fetch(`{{ request()->getBaseUrl() }}/transactions/${txId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                    .then(res => res.json().then(data => ({ status: res.status, data })))
                    .then(({ status, data }) => {
                        loading.classList.remove('show');
                        submitBtn.disabled = false;

                        if (data.success) {
                            dashTxModal.hide();
                            Toast.fire({ icon: 'success', title: data.message });
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            let messages = [];
                            if (data.errors) {
                                Object.keys(data.errors).forEach(key => {
                                    messages.push(data.errors[key][0]);
                                    const input = form.querySelector(`[name="${key}"]`);
                                    if (input) input.classList.add('is-invalid');
                                });
                            }
                            if (data.message && !data.errors) messages.push(data.message);
                            errorMsg.innerHTML = messages.join('<br>');
                            errorDiv.style.display = 'block';
                        }
                    })
                    .catch(() => {
                        loading.classList.remove('show');
                        submitBtn.disabled = false;
                        errorMsg.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                        errorDiv.style.display = 'block';
                    });
            }

            // Reset on close
            if (dashTxModalEl) {
                dashTxModalEl.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('dashTxForm').reset();
                    document.getElementById('dashTxError').style.display = 'none';
                    document.querySelectorAll('#dashTxForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
                    dashTxCategoryInput.value = '';
                    dashTxUnitInput.value = '';
                    dashTxStockInput.value = '';
                    dashTxStockWarning.style.display = 'none';
                });
            }
        @endif

        // Expose functions globally for inline event handlers and app.js theme toggle
        window.updateChartTheme = updateChartTheme;
        window.changeMonthlyChart = changeMonthlyChart;
        window.changeYearRange = changeYearRange;
        @if(auth()->user()->isAdmin())
            window.openTxEditModal = openTxEditModal;
            window.submitDashTxForm = submitDashTxForm;
        @endif
    })();
    </script>
@endpush
