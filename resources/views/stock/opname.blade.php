@extends('layouts.app')

@section('title', 'Rekap Stok')
@section('subtitle', 'Catat barang masuk dan keluar, lalu pantau batas minimum stok.')

@section('content')
    @php
        $authUser = auth()->user();
        $canInput = ($authUser->isStaff() || $authUser->isAdmin()) && !$authUser->isTeknik();
    @endphp
    <div class="animate-fade-in">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div class="item-tabs">
                <a href="{{ route('stock.index') }}" class="item-tab"><i class="bi bi-clipboard-data"></i> Rekap Stok</a>
                <a href="{{ route('stock.opname.index') }}" class="item-tab active">
                    <i class="bi bi-clipboard-check"></i> Stok Opname
                    @if($pendingCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>@endif
                </a>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('stock.opname.index') }}">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\StockOpname::STATUS_LABELS as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                @if($canInput)
                    <a href="{{ route('stock.opname.create') }}" class="btn btn-primary btn-sm" style="white-space:nowrap;">
                        <i class="bi bi-plus-lg me-1"></i> Mulai Stok Opname
                    </a>
                @endif
            </div>
        </div>

        @forelse($opnames as $so)
            @php
                $match = $so->lines->where('difference', 0)->count();
                $minus = $so->lines->where('difference', '<', 0)->count();
                $plus = $so->lines->where('difference', '>', 0)->count();
                $tz = config('app.timezone');
            @endphp
            <div class="card mb-3 so-session {{ $loop->first ? 'is-open' : '' }}">
                <div class="so-session-head" role="button" tabindex="0" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                    <i class="bi bi-chevron-right so-chevron"></i>
                    <div class="so-session-meta">
                        <div class="so-session-title">
                            {{ $so->counted_at->timezone($tz)->translatedFormat('l, d F Y') }}
                            <span class="so-session-time"><i class="bi bi-clock"></i> {{ $so->counted_at->timezone($tz)->format('H:i') }}</span>
                        </div>
                        <div class="so-session-sub">
                            {{ $so->document_number }} · Petugas: <strong>{{ $so->user->name ?? '-' }}</strong> · {{ $so->lines->count() }} item
                            <span class="so-status so-status-{{ $so->status }}">{{ $so->status_label }}</span>
                        </div>
                    </div>
                </div>
                <div class="so-session-body">
                    <div class="so-table-head">
                        <span class="fw-700"><i class="bi bi-clipboard-check text-primary-custom me-2"></i>Stok Opname</span>
                        <div class="so-counters">
                            <span class="so-counter is-match" title="Cocok"><i class="bi bi-check-circle-fill"></i> {{ $match }}</span>
                            <span class="so-counter is-minus" title="Kurang"><i class="bi bi-dash-circle-fill"></i> {{ $minus }}</span>
                            <span class="so-counter is-plus" title="Lebih"><i class="bi bi-plus-circle-fill"></i> {{ $plus }}</span>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center">Aktual</th>
                                    <th class="text-center">Selisih</th>
                                    <th>Petugas SO</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($so->lines as $i => $line)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-600">{{ $line->item->name ?? 'Barang dihapus' }}</td>
                                        <td>{{ $line->item->category ?? '-' }}</td>
                                        <td>{{ $line->item->unit ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($line->system_stock) }}</td>
                                        <td class="text-center fw-700">{{ number_format($line->actual_stock) }}</td>
                                        <td class="text-center">
                                            @if($line->difference === 0)
                                                <i class="bi bi-check-circle-fill text-success" title="Cocok"></i>
                                            @else
                                                <span class="fw-700 {{ $line->difference < 0 ? 'text-danger' : 'so-text-plus' }}">
                                                    {{ $line->difference > 0 ? '+' : '' }}{{ number_format($line->difference) }}
                                                </span>
                                                @if($line->adjustment)
                                                    <div style="font-size:10.5px;" class="text-muted">disesuaikan</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td style="font-size:12.5px;">{{ $so->user->name ?? '-' }}</td>
                                        <td style="font-size:12.5px;color:var(--text-secondary);">{{ $line->notes ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="so-session-foot">
                        <div style="font-size:12.5px;color:var(--text-secondary);">
                            @if($so->notes)<div><strong>Catatan:</strong> {{ $so->notes }}</div>@endif
                            @if($so->verifier)
                                <div>{{ $so->status === 'verified' ? 'Diverifikasi' : 'Ditolak' }} oleh <strong>{{ $so->verifier->name }}</strong>
                                    · {{ $so->verified_at?->timezone($tz)->format('d/m/Y H:i') }}</div>
                            @endif
                        </div>
                        @if($so->status === 'submitted' && $authUser->isAdmin())
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('stock.opname.reject', $so) }}" id="soReject-{{ $so->id }}">@csrf</form>
                                <form method="POST" action="{{ route('stock.opname.verify', $so) }}" id="soVerify-{{ $so->id }}">@csrf</form>
                                <button type="button" class="btn btn-outline-danger btn-sm"
                                    onclick="swalConfirm('Tolak Stok Opname?', 'Stok tidak akan diubah.', 'warning', 'Ya, Tolak', '#soReject-{{ $so->id }}')">
                                    <i class="bi bi-x-lg me-1"></i> Tolak
                                </button>
                                <button type="button" class="btn btn-success btn-sm"
                                    onclick="swalConfirm('Verifikasi & Sesuaikan Stok?', '{{ $minus + $plus }} barang dengan selisih akan dibuat transaksi penyesuaian (IN untuk lebih, OUT untuk kurang).', 'question', 'Ya, Verifikasi', '#soVerify-{{ $so->id }}')">
                                    <i class="bi bi-patch-check me-1"></i> Verifikasi & Sesuaikan Stok
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body empty-state" style="padding:40px 10px;text-align:center;">
                    <i class="bi bi-clipboard-check" style="font-size:42px;opacity:.35;"></i>
                    <h6 class="mt-2">Belum ada stok opname</h6>
                    @if($canInput)
                        <a href="{{ route('stock.opname.create') }}" class="btn btn-primary btn-sm mt-2"><i class="bi bi-plus-lg me-1"></i> Mulai Stok Opname</a>
                    @endif
                </div>
            </div>
        @endforelse

        @if($opnames->hasPages())
            <div class="d-flex justify-content-center mt-3">{{ $opnames->links('pagination.custom') }}</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.so-session-head').forEach(function (head) {
            const toggle = function () {
                const card = head.closest('.so-session');
                const open = card.classList.toggle('is-open');
                head.setAttribute('aria-expanded', open ? 'true' : 'false');
            };
            head.addEventListener('click', toggle);
            head.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
        });
    </script>
@endpush
