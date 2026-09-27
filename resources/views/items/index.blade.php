@extends('layouts.app')

@php
$isTeknik = isset($saBidang) ? $saBidang === 'teknik' : auth()->user()->bidang === 'teknik';
@endphp

@section('title', $isTeknik ? 'Master Stock On Hand (SOH)' : 'Master Barang')
@section('subtitle', $isTeknik ? '' : 'Kelola daftar barang inventory')

@section('content')
@php
$itemLabel = $isTeknik ? 'Data Master SOH' : 'Barang';
$itemLowerLabel = $isTeknik ? 'spare part' : 'barang';
$activeStockStatus = request('stock_status');
$sohTotalUrl = route('items.index', array_merge(request()->except(['stock_status', 'page']), ['sa_bidang' => $saBidang ?? '']));
$sohLowUrl = route('items.index', array_merge(request()->except(['stock_status', 'page']), ['stock_status' => 'low', 'sa_bidang' => $saBidang ?? '']));
$sohCriticalUrl = route('items.index', array_merge(request()->except(['stock_status', 'page']), ['stock_status' => 'critical', 'sa_bidang' => $saBidang ?? '']));
$itemDetailData = [];
foreach ($items as $itemRow) {
$itemDetailData[$itemRow->id] = [
'name' => $itemRow->name,
'no_normalisasi' => $itemRow->no_normalisasi ?: '-',
'category' => $itemRow->category,
'component' => $itemRow->component ?: '-',
'bidang' => $itemRow->bidang ? ucfirst($itemRow->bidang) : '-',
'lokasi' => $itemRow->lokasi ?: '-',
'volume' => $itemRow->volume ?? '-',
'ship_unloader' => $itemRow->stock_ship_unloader_label,
'unit' => $itemRow->unit,
'min_stock' => $itemRow->min_stock,
'current_stock' => $itemRow->current_stock,
];
}
@endphp
<div class="animate-fade-in">
@php
    $nameSortUrl = route('items.index', array_merge(
        request()->except(['name_sort', 'page']),
        ['name_sort' => $nextNameSort]
    ));
    $nameSortTitle = $nameSort === 'asc' ? 'Z-A' : 'A-Z';
    $nameSortLabel = $nameSort === 'asc' ? 'Z ke A' : 'A ke Z';
    $nameSortIcon = $nameSort === 'asc' ? 'bi-sort-alpha-down-alt' : 'bi-sort-alpha-down';
@endphp
    <!-- Header Actions Wrapper (for header redirection) -->
    <div class="header-action-wrapper d-none">
        <div class="section-header-actions">
            <form method="GET" action="{{ route('items.index') }}">
                @if(!empty($saBidang))
                    <input type="hidden" name="sa_bidang" value="{{ $saBidang }}">
                @endif

                {{-- Baris 1 : Search + Reset --}}
                <div class="action-row-1 items-filter-row d-flex align-items-center gap-2" style="flex-wrap: nowrap !important; width: 100%;">
                    <div id="itemSearchWrapper" class="item-search-wrapper" style="flex: 1 1 auto !important; width: auto !important; min-width: 0 !important;">
                        <input
                            type="text"
                            id="itemsSearchInput"
                            class="form-control form-control-sm"
                            name="search"
                            value="{{ request('search') }}"
                            autocomplete="off"
                            placeholder="Cari {{ $itemLowerLabel }}...">

                        <div id="itemSearchSuggestions"
                            class="autocomplete-suggestions"
                            style="display:none;">
                        </div>
                    </div>

                    <a href="{{ route('items.index', !empty($saBidang) ? ['sa_bidang' => $saBidang] : []) }}"
                        class="btn btn-reset btn-outline-teknik"
                        style="flex: 0 0 auto !important;"
                        title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise fs-5"></i>
                    </a>
                </div>

                {{-- Baris 2 : Tambah --}}
                <div class="action-row-2">
                    <button type="button"
                        class="btn btn-primary w-100"
                        onclick="openItemModal()">
                        <i class="bi bi-plus-lg"></i>
                        Tambah
                    </button>
                </div>

                {{-- Baris 3 : Kelola --}}
                <div class="action-row-3">
                    <a href="{{ route('items.lookups.index') }}"
                        class="btn {{ $isTeknik ? 'btn-outline-teknik' : 'btn-outline-primary' }} w-100">
                        <i class="bi bi-sliders2"></i>
                        Kelola
                    </a>
                </div>

            </form>
        </div>
    </div>

    @if($isTeknik)
    <div class="soh-filter-cards mb-4">
        <div class="soh-filter-card-slot">
            <a href="{{ $sohTotalUrl }}" class="soh-filter-card soh-total {{ blank($activeStockStatus) ? 'active' : '' }}">
                <div>
                    <div class="soh-filter-title">Total SOH Items</div>
                    <div class="soh-filter-value">
                        {{ number_format($stockSummary['total'] ?? 0) }}
                        <span>Items</span>
                    </div>
                    <div class="soh-filter-caption">Across all categories</div>
                </div>
                <div class="soh-filter-icon">
                    <i class="bi bi-stack"></i>
                </div>
            </a>
        </div>
        <div class="soh-filter-card-slot">
            <a href="{{ $sohLowUrl }}" class="soh-filter-card soh-low {{ $activeStockStatus === 'low' ? 'active' : '' }}">
                <div>
                    <div class="soh-filter-title">Low Stock Status</div>
                    <div class="soh-filter-value">
                        {{ number_format($stockSummary['low'] ?? 0) }}
                        <span>Items</span>
                    </div>
                    <div class="soh-filter-caption">Requires reorder soon</div>
                </div>
                <div class="soh-filter-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </a>
        </div>
        <div class="soh-filter-card-slot">
            <a href="{{ $sohCriticalUrl }}" class="soh-filter-card soh-critical {{ $activeStockStatus === 'critical' ? 'active' : '' }}">
                <div>
                    <div class="soh-filter-title">Critical Status</div>
                    <div class="soh-filter-value">
                        {{ number_format($stockSummary['critical'] ?? 0) }}
                        <span>Items</span>
                    </div>
                    <div class="soh-filter-caption">Immediate purchase required</div>
                </div>
                <div class="soh-filter-icon">
                    <i class="bi bi-radioactive"></i>
                </div>
            </a>
        </div>
    </div>
    @endif

    <!-- Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="items-table">
                    <thead>
                        @if($isTeknik)
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>No. Normalisasi</th>
                            <th>
                                <span class="date-sort-header">
                                    Nama Spare Part
                                    <a href="{{ $nameSortUrl }}"
                                        class="btn-date-sort"
                                        title="Urutkan {{ $nameSortLabel }}"
                                        aria-label="Urutkan nama {{ $nameSortLabel }}">
                                        <i class="bi {{ $nameSortIcon }}"></i>
                                    </a>
                                </span>
                            </th>
                            <th>Tipe Barang</th>
                            <th>Komponen</th>                          
                            <th>Store Room</th>
                            <th>Ship Unloader</th>
                            <th>Low Limit
                                (Aktual)</th>
                            <th>Min. Stok</th>
                            <th>Volume</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                        @else
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>
                                <span class="date-sort-header">
                                    Nama Barang
                                    <a href="{{ $nameSortUrl }}"
                                        class="btn-date-sort"
                                        title="Urutkan {{ $nameSortLabel }}"
                                        aria-label="Urutkan nama {{ $nameSortLabel }}">
                                        <i class="bi {{ $nameSortIcon }}"></i>
                                    </a>
                                </span>
                            </th>
                            <th>Kategori</th>
                            @if(auth()->user()->isSuperAdmin())
                            <th>Bidang</th>
                            @endif
                            <th>Satuan</th>
                            <th>Min. Stok</th>
                            <th>Stok Saat Ini</th>
                            <th>Status</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($items as $index => $item)
                        <tr
                            @if($isTeknik)
                                data-item-id="{{ $item->id }}"
                                data-name="{{ strtolower($item->name) }}"
                                data-normalisasi="{{ strtolower($item->no_normalisasi ?? '') }}"
                                data-component="{{ strtolower($item->component ?? '') }}"
                                data-category="{{ strtolower($item->category ?? '') }}"
                            @else
                                data-item-id="{{ $item->id }}"
                                data-name="{{ strtolower($item->name) }}"
                                data-category="{{ strtolower($item->category ?? '') }}"
                            @endif
                        >
                            <td>{{ $items->firstItem() + $index }}</td>
                            @if($isTeknik)
                            <td style="font-family: 'Major Mono Display', monospace; font-size:16px;" >
                                @if($item->current_stock < $item->min_stock)
                                    <span class="badge-status badge-rejected position-relative badge-critical-teknik">
                                        {{ $item->no_normalisasi ?? '-' }}
                                    </span>
                                @elseif($item->current_stock == $item->min_stock)
                                    <span class="badge-status technical-soh-norm norm-out">
                                        {{ $item->no_normalisasi ?? '-' }}
                                    </span>
                                @else
                                    <span class="badge-status badge-approved">
                                        {{ $item->no_normalisasi ?? '-' }}
                                    </span>
                                @endif
                            </td>
                            <td class="fw-600 col-name-wrap">{{ $item->name }}</td>
                            <td>{{ $item->component ?? '-' }}</td>
                            <td>{{ $item->category }}</td>                           
                            <td class="text-nowrap align-middle">
                                <div class="d-flex flex-nowrap align-items-center gap-1">
                                    {{ $item->lokasi ?? '-' }}
                                </div>
                            </td>
                            <td class="text-nowrap align-middle">
                                @php
                                $activeShips = collect(explode(',', (string) $item->stock_ship_unloader))->filter()->all();
                                @endphp
                                <div class="d-flex flex-nowrap align-items-center gap-1">
                                    @foreach([1, 2, 3, 4] as $ship)
                                    <span class="badge {{ in_array((string) $ship, $activeShips, true) ? 'badge-ship-active' : 'badge-ship-inactive' }}">
                                        {{ $ship }}
                                    </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="fw-700 text-center {{ $item->current_stock < $item->min_stock ? 'stock-value-critical' : ($item->current_stock == $item->min_stock ? 'stock-value-low' : 'stock-value-ready') }}">
                                {{ $item->current_stock }}
                            </td>
                            <td>{{ $item->min_stock }}</td>
                            <td>{{ $item->volume ?? '-' }}</td>
                            <td>{{ $item->unit }}</td>
                            @else
                            <td class="fw-600">{{ $item->name }}</td>
                            <td>{{ $item->category }}</td>
                            @if(auth()->user()->isSuperAdmin())
                            <td>{{ ucfirst($item->bidang) }}</td>
                            @endif
                            <td>{{ $item->unit }}</td>
                            <td>{{ $item->min_stock }}</td>
                            <td class="fw-700 {{ $item->current_stock <= 0 ? 'stock-value-critical' : ($item->current_stock <= $item->min_stock ? 'stock-value-low' : 'stock-value-ready') }}">
                                {{ $item->current_stock }}
                            </td>
                            @endif

                            <td class="text-nowrap align-middle">
                                <div class="d-flex flex-nowrap align-items-center gap-1">

                                    @if($isTeknik)

                                    {{-- TEKNIK --}}
                                    @if($item->current_stock < $item->min_stock)
                                        <span class="badge-status badge-rejected position-relative badge-critical-teknik">
                                            Critical
                                            <span class="ping-container">
                                                <span class="ping-dot-pulse"></span>
                                                <span class="ping-dot-core"></span>
                                            </span>
                                        </span>

                                        @elseif($item->current_stock == $item->min_stock)
                                        <span class="badge-status technical-soh-norm norm-out">
                                            Low Stock
                                        </span>

                                        @else
                                        <span class="badge-status badge-in-stock">
                                            In Stock
                                        </span>
                                        @endif

                                        @else

                                        {{-- UMUM (LOGIKA LAMA TIDAK DIUBAH) --}}
                                        @if($item->current_stock <= 0)
                                            <span class="badge-status badge-rejected">
                                            <i class="bi bi-x-circle-fill"></i>
                                            Out of Stock
                                            </span>

                                            @elseif($item->current_stock <= $item->min_stock)
                                                <span class="badge-status technical-soh-norm norm-out">
                                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                                    Request Order
                                                </span>

                                                @else
                                                <span class="badge-status badge-approved">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    Ready
                                                </span>
                                                @endif

                                                @endif

                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn-action view btn-item-detail-open" title="Lihat Detail"
                                        data-item-id="{{ $item->id }}">
                                        <i class="bi bi-info-circle-fill"></i>
                                    </button>

                                    <button type="button" class="btn-action edit" title="Edit"
                                        onclick="openItemModal({{ $item->id }})">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>

                                    <form action="{{ route('items.destroy', $item) }}" method="POST"
                                        id="deleteItem-{{ $item->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn-action delete" title="Hapus"
                                            @if($isTeknik)
                                                onclick="swalConfirm('Hapus Permanen?', 'Menghapus master {{ $item->no_normalisasi ?? '-' }} juga akan mempengaruhi kalkulasi akumulasi SOH.', 'warning', 'Ya, Hapus Data', '#deleteItem-{{ $item->id }}')"
                                            @else
                                                onclick="swalConfirm('Hapus {{ $itemLabel }}', 'Yakin hapus {{ $itemLowerLabel }} ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteItem-{{ $item->id }}')"
                                            @endif
                                        >
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="no-data-row">
                            <td colspan="{{ $isTeknik ? 13 : (auth()->user()->isSuperAdmin() ? 9 : 8) }}">
                                <i class="bi bi-inbox"
                                    style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                Belum ada data {{ $itemLowerLabel }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($items->hasPages())
    <div class="d-flex justify-content-center mt-3">
        {{ $items->links('pagination.custom') }}
    </div>
    @endif
</div>

<div class="modal fade inventrack-modal text-uppercase" id="itemDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle-fill me-2"></i>Detail {{ $itemLabel }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3" id="itemDetailGrid"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@if($isTeknik)
{{-- Technical Item Modal (Create/Edit) --}}
<div class="modal fade inventrack-modal" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="position:relative;">
            <div class="modal-loading-overlay" id="itemLoading">
                <div class="modal-spinner"></div>
            </div>
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalTitle">
                    <i class="bi bi-plus-circle-fill"></i> <span>Tambah {{ $itemLabel }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="modal-error-alert" id="itemError">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span id="itemErrorMsg"></span>
                </div>
                <form id="itemForm" novalidate class="text-uppercase">
                    <input type="hidden" id="itemId" value="">
                    <input type="hidden" id="itemMethod" value="POST">
                    <input type="hidden" name="bidang" value="teknik">

                    <div class="mb-3">
                        <label class="form-label">Nama {{ $itemLabel }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Masukkan nama {{ $itemLowerLabel }}" required
                            id="itemName">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Komponen <span class="text-danger">*</span></label>
                            <input type="text" name="category" list="category-list" class="form-control"
                                placeholder="Pilih atau ketik Komponen" required id="itemCategory">
                            <datalist id="category-list">
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                                    @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" list="unit-list" class="form-control"
                                placeholder="Pilih atau ketik satuan" required id="itemUnit">
                            <datalist id="unit-list">
                                @foreach($units as $u)
                                <option value="{{ $u }}">
                                    @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Minimum Stok <span class="text-danger">*</span></label>
                        <input type="number" name="min_stock" class="form-control" min="0" required id="itemMinStock"
                            value="0">
                        <small class="text-muted text-lowercase">Sistem akan memberikan peringatan jika stok di bawah jumlah minimum</small>
                    </div>

                    <div class="technical-item-fields">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">No. Normalisasi<span class="text-danger">*</span></label>
                                <input type="text" name="no_normalisasi" class="form-control" placeholder="00.000.000.0000" id="itemNoNormalisasi" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Volume<span class="text-danger">*</span></label>
                                <input type="number" name="volume" class="form-control" min="0" id="itemVolume" value="0" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipe Barang<span class="text-danger">*</span></label>
                                <input type="text" name="component" list="component-list" class="form-control" placeholder="Pilih atau ketik tipe barang" id="itemComponent" required>
                                <datalist id="component-list">
                                    @foreach($components as $component)
                                    <option value="{{ $component }}">
                                        @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Store room<span class="text-danger">*</span></label>
                                <input type="text" name="lokasi" class="form-control" placeholder="Letak penyimpanan" id="itemLokasi" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="itemSubmitBtn" onclick="submitItemForm()">
                    <i class="bi bi-check-lg"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
@else
{{-- General Item Modal (Create/Edit) --}}
<div class="modal fade inventrack-modal" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="position:relative;">
            <div class="modal-loading-overlay" id="itemLoading">
                <div class="modal-spinner"></div>
            </div>
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalTitle">
                    <i class="bi bi-plus-circle-fill"></i> <span>Tambah {{ $itemLabel }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="modal-error-alert" id="itemError">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    <span id="itemErrorMsg"></span>
                </div>
                <form id="itemForm" novalidate>
                    <input type="hidden" id="itemId" value="">
                    <input type="hidden" id="itemMethod" value="POST">
                    <input type="hidden" name="bidang" value="umum">

                    <div class="mb-3">
                        <label class="form-label">Nama {{ $itemLabel }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Masukkan nama {{ $itemLowerLabel }}" required
                            id="itemName">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <input type="text" name="category" list="category-list" class="form-control"
                                placeholder="Pilih atau ketik kategori" required id="itemCategory">
                            <datalist id="category-list">
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                                    @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <input type="text" name="unit" list="unit-list" class="form-control"
                                placeholder="Pilih atau ketik satuan" required id="itemUnit">
                            <datalist id="unit-list">
                                @foreach($units as $u)
                                <option value="{{ $u }}">
                                    @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Minimum Stok <span class="text-danger">*</span></label>
                        <input type="number" name="min_stock" class="form-control" min="0" required id="itemMinStock"
                            value="0">
                        <small class="text-muted">Sistem akan memberikan peringatan jika stok di bawah jumlah minimum</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Batal</button>
                <button type="button" class="btn btn-primary" id="itemSubmitBtn" onclick="submitItemForm()">
                    <i class="bi bi-check-lg"></i> Simpan
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
    window.__itemDetailData = @json($itemDetailData);
    const isTeknikItem = @json($isTeknik);
    const itemLabel = @json($itemLabel);
    const itemLowerLabel = @json($itemLowerLabel);
    const itemModalEl = document.getElementById('itemModal');
    const itemModal = new bootstrap.Modal(itemModalEl);
    const itemDetailModalEl = document.getElementById('itemDetailModal');
    const itemDetailModal = new bootstrap.Modal(itemDetailModalEl);

    function appendDetailCell(container, label, value) {
        const wrap = document.createElement('div');
        wrap.className = 'col-sm-6';
        const muted = document.createElement('div');
        muted.className = 'text-muted';
        muted.style.fontSize = '12px';
        muted.textContent = label;
        const strong = document.createElement('div');
        strong.className = 'fw-700';
        strong.textContent = value || '-';
        wrap.appendChild(muted);
        wrap.appendChild(strong);
        container.appendChild(wrap);
    }

    document.querySelectorAll('.btn-item-detail-open').forEach(function(button) {
        button.addEventListener('click', function() {
            const data = window.__itemDetailData[this.dataset.itemId];
            if (!data) return;
            const grid = document.getElementById('itemDetailGrid');
            grid.replaceChildren();
            if (isTeknikItem) {
                appendDetailCell(grid, 'No Normalisasi', data.no_normalisasi);
                appendDetailCell(grid, 'Nama Spare Part', data.name);
                appendDetailCell(grid, 'Kategori', data.category);
                appendDetailCell(grid, 'Komponen', data.component);
                appendDetailCell(grid, 'Lokasi', data.lokasi);
                appendDetailCell(grid, 'Ship Unloader', data.ship_unloader);
                appendDetailCell(grid, 'Volume', data.volume);
            } else {
                appendDetailCell(grid, 'Nama Barang', data.name);
                appendDetailCell(grid, 'Kategori', data.category);
                appendDetailCell(grid, 'Bidang', data.bidang);
            }
            appendDetailCell(grid, 'Satuan', data.unit);
            appendDetailCell(grid, 'Min Stok', data.min_stock);
            appendDetailCell(grid, 'Stok Saat Ini', data.current_stock);
            itemDetailModal.show();
        });
    });

    function openItemModal(id = null) {
        // Reset form
        document.getElementById('itemForm').reset();
        document.getElementById('itemError').style.display = 'none';
        document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.getElementById('itemMinStock').value = '0';
        if (document.getElementById('itemCurrentStock')) {
            document.getElementById('itemCurrentStock').value = '0';
        }
        if (document.getElementById('itemVolume')) {
            document.getElementById('itemVolume').value = '0';
        }
        paintItemShipBadges('');
        toggleTechnicalItemFields();

        if (id) {
            // Edit mode — load data
            document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-pencil-fill"></i> <span>Edit ' + itemLabel + '</span>';
            document.getElementById('itemId').value = id;
            document.getElementById('itemMethod').value = 'PUT';
            document.getElementById('itemSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update';

            const loading = document.getElementById('itemLoading');
            loading.classList.add('show');
            itemModal.show();

            fetch(`{{ request()->getBaseUrl() }}/items/${id}/edit-data`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    loading.classList.remove('show');
                    document.getElementById('itemName').value = data.name;
                    document.getElementById('itemCategory').value = data.category;
                    document.getElementById('itemUnit').value = data.unit;
                    document.getElementById('itemMinStock').value = data.min_stock;
                    if (document.getElementById('itemNoNormalisasi')) {
                        document.getElementById('itemNoNormalisasi').value = data.no_normalisasi || '';
                    }
                    if (document.getElementById('itemLokasi')) {
                        document.getElementById('itemLokasi').value = data.lokasi || '';
                    }
                    if (document.getElementById('itemCurrentStock')) {
                        document.getElementById('itemCurrentStock').value = data.current_stock || 0;
                    }
                    if (document.getElementById('itemComponent')) {
                        document.getElementById('itemComponent').value = data.component || '';
                    }
                    if (document.getElementById('itemVolume')) {
                        document.getElementById('itemVolume').value = data.volume || 0;
                    }
                    paintItemShipBadges(data.stock_ship_unloader || '');
                    if (document.getElementById('itemBidang')) {
                        document.getElementById('itemBidang').value = data.bidang || '';
                    }
                    toggleTechnicalItemFields();
                })
                .catch(() => {
                    loading.classList.remove('show');
                    document.getElementById('itemErrorMsg').textContent = 'Gagal memuat data ' + itemLowerLabel + '.';
                    document.getElementById('itemError').style.display = 'block';
                });
        } else {
            // Create mode
            document.getElementById('itemModalTitle').innerHTML = '<i class="bi bi-plus-circle-fill"></i> <span>Tambah ' + itemLabel + '</span>';
            document.getElementById('itemId').value = '';
            document.getElementById('itemMethod').value = 'POST';
            document.getElementById('itemSubmitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Simpan';
            toggleTechnicalItemFields();
            itemModal.show();
        }
    }

    function paintItemShipBadges(value) {
        const ships = (value || '').split(',').filter(Boolean);
        document.querySelectorAll('#itemShipBadges [data-ship]').forEach(el => {
            const active = ships.includes(el.dataset.ship);
            el.className = 'badge badge-ship-inactive' + (active ? 'bg-primary' : 'bg-light text-muted border');
        });
    }

    function toggleTechnicalItemFields() {
        const wrapper = document.querySelector('.technical-item-fields');
        if (!wrapper) return;
        const bidangSelect = document.getElementById('itemBidang');
        const show = !bidangSelect || bidangSelect.value === 'teknik';
        wrapper.style.display = show ? '' : 'none';
    }

    const itemBidangSelect = document.getElementById('itemBidang');
    if (itemBidangSelect) {
        itemBidangSelect.addEventListener('change', toggleTechnicalItemFields);
    }

    function submitItemForm() {
        const form = document.getElementById('itemForm');
        const errorDiv = document.getElementById('itemError');
        const errorMsg = document.getElementById('itemErrorMsg');
        const loading = document.getElementById('itemLoading');
        const submitBtn = document.getElementById('itemSubmitBtn');
        const itemId = document.getElementById('itemId').value;
        const method = document.getElementById('itemMethod').value;

        // Clear errors
        errorDiv.style.display = 'none';
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        loading.classList.add('show');
        submitBtn.disabled = true;

        const formData = new FormData(form);
        const url = itemId ? `{{ request()->getBaseUrl() }}/items/${itemId}` : `{{ request()->getBaseUrl() }}/items`;

        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json().then(data => ({
                status: res.status,
                data
            })))
            .then(({
                status,
                data
            }) => {
                loading.classList.remove('show');
                submitBtn.disabled = false;

                if (data.success) {
                    itemModal.hide();
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
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

    // Reset modal on close
    itemModalEl.addEventListener('hidden.bs.modal', function() {
        document.getElementById('itemForm').reset();
        document.getElementById('itemError').style.display = 'none';
        document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    });

    const searchInput = document.getElementById('itemsSearchInput');
const suggestionsBox = document.getElementById('itemSearchSuggestions');
const searchWrapper = document.getElementById('itemSearchWrapper');

const isTeknik = @json($isTeknik);
const autocompleteUrl = @json(route('api.autocomplete.items'));
const saBidang = @json($saBidang ?? '');

let debounceTimer = null;

searchInput.addEventListener('input', function () {

    const keyword = this.value.trim();

    if (!keyword) {
        suggestionsBox.style.display = 'none';
        suggestionsBox.innerHTML = '';
        return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const params = new URLSearchParams({ q: keyword });
        if (saBidang) params.set('sa_bidang', saBidang);

        fetch(`${autocompleteUrl}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(results => renderSuggestions(results))
        .catch(() => { suggestionsBox.style.display = 'none'; });
    }, 200);

});

function renderSuggestions(items) {

    if (!items.length) {

        suggestionsBox.innerHTML =
            '<div class="autocomplete-no-result">Tidak ada barang ditemukan</div>';

        suggestionsBox.style.display = 'block';
        return;
    }

    suggestionsBox.innerHTML = items.map(item => `

    <div class="autocomplete-item"
        data-name="${item.name}">

        <div class="autocomplete-icon">
            <i class="bi bi-box-seam"></i>
        </div>

        <div class="autocomplete-content">

            <div class="autocomplete-title">
                ${item.name}
            </div>

            <div class="autocomplete-subtitle">
                ${
                    isTeknik
                        ? `${item.normalisasi || '-'} • ${item.component || '-'}`
                        : `${item.category || '-'}`
                }
            </div>

        </div>

    </div>

    `).join('');

    suggestionsBox.style.display = 'block';

    suggestionsBox.querySelectorAll('.autocomplete-item')
        .forEach(el => {

            el.addEventListener('click', function () {
                const name = this.dataset.name;
                searchInput.value = name;
                suggestionsBox.style.display = 'none';
                // Submit the form to trigger server-side search
                searchInput.closest('form').submit();
            });

        });
}

/*
|--------------------------------------------------------------------------
| Tutup suggestion ketika klik di luar area search
|--------------------------------------------------------------------------
*/
document.addEventListener('click', function (e) {

    if (
        searchWrapper &&
        !searchWrapper.contains(e.target)
    ) {
        suggestionsBox.style.display = 'none';
    }

});

/*
|--------------------------------------------------------------------------
| Tampilkan lagi suggestion ketika input difokuskan
|--------------------------------------------------------------------------
*/
    searchInput.addEventListener('focus', function () {
        if (
            this.value.trim() !== '' &&
            suggestionsBox.innerHTML.trim() !== ''
        ) {
            suggestionsBox.style.display = 'block';
        }
    });

    // Expose functions globally for inline event handlers
    window.openItemModal = openItemModal;
    window.submitItemForm = submitItemForm;

})();
</script>
@endpush