@php
    $isTeknik = isset($saBidang) ? $saBidang === 'teknik' : auth()->user()->bidang === 'teknik';
    $activeTransactionType = request('type') === 'out' ? 'out' : 'in';
    $suffix = $isTeknik ? 'teknik-' . $activeTransactionType : 'umum';
    $jsSuffix = str_replace('-', '_', $suffix);
    $currentSort = request('sort', 'latest') === 'oldest' ? 'oldest' : 'latest';
    $nextSort = $currentSort === 'oldest' ? 'latest' : 'oldest';
    $showCreateButton = $showCreateButton ?? true;
@endphp

<div id="transactionsTableRegion">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fs-5 fw-semibold">
                @if($isTeknik)
                    @if($activeTransactionType === 'in')
                        <i class="bi bi-table text-success me-2"></i>Recent GR Log
                    @else
                        <i class="bi bi-table text-warning me-2"></i>Recent GI Log
                    @endif
                @else
                    <i class="bi bi-table text-primary-custom me-2"></i>Daftar Transaksi
                @endif
            </span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted" style="font-size:13px;">Total: {{ $transactions->total() }} transaksi</span>
                @if($showCreateButton)
                    <button type="button" class="btn btn-primary btn-sm" onclick="openTransactionModal_{{ $jsSuffix }}()">
                        <i class="bi bi-plus-lg"></i> Input Transaksi
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table" id="transactions-table">
                    <thead>
                        @if($isTeknik)
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>
                                    <span class="date-sort-header">
                                        Tanggal
                                        <button type="button" class="btn-date-sort js-date-sort"
                                            data-sort="{{ $nextSort }}"
                                            title="Urutkan {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}"
                                            aria-label="Urutkan tanggal {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </span>
                                </th>
                                <th>No Normalisasi</th>
                                <th class="col-name-wrap">Nama Barang</th>
                                <th>Komponen</th>
                                <th>Tipe Barang</th>
                                <th style="width: 100px;">Ship Unloader</th>
                                <th>Lokasi</th>
                                <th class="text-center">Volume</th>
                                <th class="text-center">Jumlah</th>
                                <th>Satuan</th>
                                <th>User</th>
                                <th class="text-center" style="width:72px;">Aksi</th>
                            </tr>
                        @else
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>
                                    <span class="date-sort-header">
                                        Tanggal
                                        <button type="button" class="btn-date-sort js-date-sort"
                                            data-sort="{{ $nextSort }}"
                                            title="Urutkan {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}"
                                            aria-label="Urutkan tanggal {{ $nextSort === 'latest' ? 'terbaru' : 'terlama' }}">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </button>
                                    </span>
                                </th>
                                <th>Jenis</th>
                                <th class="col-name-wrap">Barang</th>
                                <th>Kategori</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
                                <th>User</th>
                                <th>Keterangan</th>
                                @if(true) {{-- Status tampil untuk semua user Umum (transaksi rejected jadi jelas) --}}
                                    <th>Status</th>
                                @endif
                                <th class="text-center" style="width:72px;">Aksi</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @forelse($transactions as $index => $tx)
                            <tr data-id="{{ $tx->id }}"
                                data-name="{{ $tx->item->name ?? '' }}"
                                data-normalisasi="{{ $tx->no_normalisasi ?? $tx->item->no_normalisasi ?? '' }}"
                                data-component="{{ $tx->item->component ?? '' }}"
                                data-category="{{ $tx->item->category ?? '' }}">
                                <td>{{ $transactions->firstItem() + $index }}</td>
                                <td>{{ $tx->date->format('d/m/Y') }}</td>
                                @if($isTeknik)
                                    <td><span class="{{ $tx->type === 'in' ? 'norm-text-in' : 'norm-text-out' }}">{{ $tx->no_normalisasi ?? $tx->item->no_normalisasi ?? '-' }}</span></td>
                                    <td class="fw-600 col-name-wrap">{{ $tx->item->name ?? '-' }}</td>
                                    <td>{{ $tx->item->component ?? '-' }}</td>
                                    <td>{{ $tx->item->category ?? '-' }}</td>
                                    <td class="text-nowrap align-middle" style="width: 100px;">
                                        @php
                                            $activeShips = collect(explode(',', (string) $tx->ship_unloader))
                                                ->filter()
                                                ->all();
                                            $isAllActive = count($activeShips) === 4;
                                        @endphp

                                        <div class="d-flex flex-nowrap align-items-center gap-1">
                                            @if($isAllActive)
                                                <span class="badge badge-all" style="width: auto; min-width: 24px; padding: 0 6px !important;">
                                                    ALL
                                                </span>
                                            @elseif(count($activeShips) > 0)
                                                @foreach($activeShips as $ship)
                                                    <span class="badge {{ $tx->type === 'out' ? 'badge-ship-active badge-ship-active-issue' : 'badge-ship-active' }}">
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
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">

                                            <a href="javascript:void(0)"
                                                class="action-icon btn-transaction-detail-open"
                                                title="Lihat Detail"
                                                data-transaction-id="{{ $tx->id }}">
                                                <i class="bi bi-info-circle-fill fs-6"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                                class="action-icon text-warning"
                                                title="Edit"
                                                onclick="openTransactionModal_{{ $jsSuffix }}({{ $tx->id }})">
                                                <i class="bi bi-pencil-fill fs-6"></i>
                                            </a>

                                            <a href="javascript:void(0)"
                                                class="action-icon text-danger"
                                                title="Hapus"
                                                onclick="swalConfirm('Hapus Transaksi', 'Yakin hapus transaksi ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteTx-{{ $tx->id }}')">
                                                <i class="bi bi-trash-fill fs-6"></i>
                                            </a>

                                            <form action="{{ route('transactions.destroy', $tx) }}"
                                                method="POST"
                                                id="deleteTx-{{ $tx->id }}"
                                                class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>

                                        </div>
                                    </td>
                                @else
                                    <td>
                                        <span class="badge-status badge-{{ $tx->type }}">
                                            <i class="bi bi-arrow-{{ $tx->type === 'in' ? 'down' : 'up' }}-circle-fill" style="font-size:10px;"></i>
                                            {{ $tx->type_label }}
                                        </span>
                                    </td>
                                    <td class="fw-600 col-name-wrap">{{ $tx->item->name ?? '-' }}</td>
                                    <td>{{ $tx->item->category ?? '-' }}</td>
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
                                    <td>{{ $tx->price === null ? '-' : 'Rp ' . number_format($tx->price, 0, ',', '.') }}</td>
                                    <td>{{ $tx->user->name ?? '-' }}</td>
                                    <td style="min-width:200px; max-width:260px; font-size:12px; color:var(--text-secondary);">
                                        <div title="{{ $tx->description }}"
                                            style="max-width:260px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; white-space:normal; word-break:break-word; line-height:1.4;">
                                            {{ $tx->description }}
                                        </div>
                                    </td>
                                    @if(true)
                                        <td class="text-nowrap">
                                            @if($tx->status === 'pending')
                                                <span class="badge-status badge-pending">
                                                    <i class="bi bi-clock-fill"></i> Pending
                                                </span>
                                            @elseif($tx->status === 'approved')
                                                <span class="badge-status badge-approved">
                                                    <i class="bi bi-check-circle-fill"></i> Approved
                                                </span>
                                            @elseif($tx->status === 'rejected')
                                                <span class="badge-status badge-rejected">
                                                    <i class="bi bi-x-circle-fill"></i> Rejected
                                                </span>
                                            @else
                                                <span class="badge-status bg-light text-dark">
                                                    {{ ucfirst($tx->status) }}
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        @if(auth()->user()->isSuperAdmin())
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="javascript:void(0)"
                                                    class="action-icon btn-transaction-detail-open"
                                                    title="Lihat Detail"
                                                    data-transaction-id="{{ $tx->id }}">
                                                    <i class="bi bi-info-circle-fill fs-6"></i>
                                                </a>

                                                <a href="javascript:void(0)"
                                                    class="action-icon text-danger"
                                                    title="Hapus"
                                                    onclick="swalConfirm('Hapus Transaksi', 'Yakin hapus transaksi ini? Data yang sudah dihapus tidak bisa dikembalikan.', 'warning', 'Ya, Hapus', '#deleteTx-{{ $tx->id }}')">
                                                    <i class="bi bi-trash-fill fs-6"></i>
                                                </a>

                                                <form action="{{ route('transactions.destroy', $tx) }}"
                                                    method="POST"
                                                    id="deleteTx-{{ $tx->id }}"
                                                    class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        @else
                                            <button type="button" class="btn btn-md btn-transaction-detail-open"
                                                title="Lihat Detail" data-transaction-id="{{ $tx->id }}">
                                                <i class="bi bi-info-circle-fill"></i>
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr class="no-data-row">
                                <td colspan="{{ $isTeknik ? 13 : 12 }}">
                                    <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                    Belum ada data transaksi
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($transactions->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $transactions->links('pagination.custom') }}
        </div>
    @endif
</div>
