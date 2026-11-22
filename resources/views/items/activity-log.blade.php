@extends('layouts.app')

@section('title', 'Log Aktivitas Barang')
@section('subtitle', 'Riwayat seluruh aktivitas barang dalam satu halaman')

@section('content')
    @php
        $hasFilter = collect($filters)->except(['dokumen', 'per_page', $defaultRange ? 'from' : '__none'])->filter()->isNotEmpty();
        $linkFilters = collect($filters)->except(['dokumen', $defaultRange ? 'from' : '__none'])->filter()->all();
    @endphp
    <div class="animate-fade-in">
        {{-- Tab Master Barang --}}
        <div class="item-tabs mb-3">
            @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
                <a href="{{ route('items.index') }}" class="item-tab"><i class="bi bi-box-seam"></i> Daftar Barang</a>
            @endif
            <a href="{{ route('items.activity-log') }}" class="item-tab active"><i class="bi bi-clock-history"></i> Log Aktivitas</a>
        </div>

        @if($document)
            <div class="card mb-3 activity-doc-card">
                <div class="card-body d-flex flex-wrap align-items-center gap-3">
                    <div class="activity-doc-icon"><i class="bi bi-qr-code-scan"></i></div>
                    <div class="flex-grow-1">
                        <div class="text-muted" style="font-size:12px;">Dokumen dari QR</div>
                        <div class="fw-700" style="font-size:16px;">Rekap Persediaan Barang · {{ $document->document_number }}</div>
                        <div style="font-size:12.5px;color:var(--text-secondary);">
                            Cetakan ke-{{ $document->id }} · dibuat oleh <strong>{{ $document->printer->name ?? '-' }}</strong>
                            pada {{ $document->created_at?->timezone(config('app.timezone'))->translatedFormat('d F Y H.i') }} WIB
                            · {{ number_format($document->row_count) }} baris · Rp {{ number_format($document->grand_total, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('stock-requests.recap.show', $document) }}" target="_blank" class="btn btn-sm btn-outline-pdf"><i class="bi bi-file-earmark-pdf me-1"></i>Buka PDF</a>
                        <a href="{{ route('items.activity-log') }}" class="btn btn-sm btn-outline-secondary">Lihat semua aktivitas</a>
                    </div>
                </div>
            </div>
        @elseif(!empty($filters['dokumen']))
            <div class="alert alert-warning py-2" style="font-size:13px;">Dokumen <strong>{{ $filters['dokumen'] }}</strong> tidak ditemukan.</div>
        @endif

        {{-- Ringkasan per jenis --}}
        <div class="activity-chips mb-3">
            <a href="{{ route('items.activity-log', collect($linkFilters)->except('type')->all()) }}"
                class="activity-chip {{ empty($filters['type']) ? 'active' : '' }}">
                Semua <span>{{ $counts->sum() }}</span>
            </a>
            @foreach($types as $key => [$label, $icon, $color])
                <a href="{{ route('items.activity-log', array_merge($linkFilters, ['type' => $key])) }}"
                    class="activity-chip chip-{{ $color }} {{ ($filters['type'] ?? null) === $key ? 'active' : '' }}">
                    <i class="bi {{ $icon }}"></i> {{ $label }}
                    @if(empty($filters['type']) || ($filters['type'] ?? null) === $key)<span>{{ $counts[$key] ?? 0 }}</span>@endif
                </a>
            @endforeach
        </div>

        {{-- Filter --}}
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('items.activity-log') }}" class="row g-2 align-items-end">
                    @if(!empty($filters['type']))<input type="hidden" name="type" value="{{ $filters['type'] }}">@endif
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <div class="col-md-4">
                        <label class="form-label mb-1" style="font-size:12px;">Barang</label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">Semua barang</option>
                            @foreach($items as $it)
                                <option value="{{ $it->id }}" {{ (int) ($filters['item_id'] ?? 0) === $it->id ? 'selected' : '' }}>
                                    {{ $it->name }}{{ $it->category ? ' · '.$it->category : '' }}{{ $it->deleted_at ? ' (dihapus)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1" style="font-size:12px;">Oleh</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">Semua user</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ (int) ($filters['user_id'] ?? 0) === $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1" style="font-size:12px;">Dari</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label mb-1" style="font-size:12px;">Sampai</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Terapkan"><i class="bi bi-funnel"></i></button>
                        @if($hasFilter)
                            <a href="{{ route('items.activity-log') }}" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-x-lg"></i></a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if($defaultRange)
            <div class="mb-2" style="font-size:12px;color:var(--text-secondary);">
                <i class="bi bi-info-circle me-1"></i>Menampilkan 3 bulan terakhir. Ubah tanggal <strong>Dari</strong> untuk melihat riwayat lebih lama.
            </div>
        @endif
        <div class="card">
            <div class="card-header">
                <span><i class="bi bi-clock-history text-primary-custom me-2"></i>Riwayat Aktivitas</span>
                <span class="text-muted" style="font-size:12px;">{{ number_format($events->total()) }} aktivitas</span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table activity-table mb-0">
                        <thead>
                            <tr>
                                <th style="width:130px;">Waktu</th>
                                <th style="width:160px;">Aktivitas</th>
                                <th style="min-width:180px;">Barang</th>
                                <th class="text-end" style="width:100px;">Jumlah</th>
                                <th style="min-width:200px;">Detail</th>
                                <th style="width:150px;">Oleh</th>
                                <th style="width:170px;">Referensi / Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($events as $e)
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <div class="fw-600" style="font-size:13px;">{{ $e['at']?->timezone(config('app.timezone'))->format('d/m/Y') }}</div>
                                        <div class="text-muted" style="font-size:11.5px;">{{ $e['at']?->timezone(config('app.timezone'))->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <span class="activity-type type-{{ $e['color'] }}"><i class="bi {{ $e['icon'] }}"></i> {{ $e['type_label'] }}</span>
                                    </td>
                                    <td>
                                        <div class="fw-600">{{ $e['title'] }}</div>
                                        @if($e['category'])
                                            <div class="text-muted" style="font-size:11.5px;">{{ $e['category'] }}{{ $e['item_trashed'] ? ' · dihapus' : '' }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end" style="white-space:nowrap;">
                                        @if(!is_null($e['qty']))
                                            <span class="fw-700 {{ $e['qty_neutral'] ? '' : ($e['qty'] >= 0 ? 'text-success' : 'text-danger') }}">
                                                {{ !$e['qty_neutral'] && $e['qty'] > 0 ? '+' : '' }}{{ number_format($e['qty'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-muted" style="font-size:11.5px;">{{ $e['unit'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td style="font-size:12.5px;color:var(--text-secondary);">
                                        {{ $e['detail'] }}
                                        @if($e['date_note'])<div style="font-size:11px;">{{ $e['date_note'] }}</div>@endif
                                    </td>
                                    <td style="font-size:12.5px;">{{ $e['user'] }}</td>
                                    <td>
                                        @if($e['ref'])<div style="font-size:12px;">{{ $e['ref'] }}</div>@endif
                                        @if($e['status_label'])
                                            <span class="activity-status status-{{ $e['status_color'] }}">{{ $e['status_label'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="7">
                                        <i class="bi bi-clock-history" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Belum ada aktivitas untuk filter ini
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="activity-pagination mt-3">
            <div class="activity-pagination-info">
                @if($events->total() > 0)
                    Menampilkan <strong>{{ $events->firstItem() }}–{{ $events->lastItem() }}</strong> dari <strong>{{ number_format($events->total()) }}</strong> aktivitas
                @else
                    Tidak ada aktivitas
                @endif
            </div>
            <div>{{ $events->onEachSide(1)->links('pagination.custom') }}</div>
            <form method="GET" action="{{ route('items.activity-log') }}" class="activity-pagination-size">
                @foreach(collect($filters)->except(['per_page', $defaultRange ? 'from' : '__none'])->filter() as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <label for="activityPerPage">Per halaman</label>
                <select id="activityPerPage" name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach([15, 25, 50, 100] as $n)
                        <option value="{{ $n }}" {{ $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
@endsection
