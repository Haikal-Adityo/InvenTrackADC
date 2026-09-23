@extends('layouts.app')

@section('title', 'Permintaan Barang')
@section('subtitle', 'Daftar Permintaan Barang dari Karyawan')

@section('content')
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
        $canProcessAsAdmin = auth()->user()->isAdmin() || (auth()->user()->isManager() && $isTeknik);
        $stockActionData = [];
        foreach ($requests as $r) {
            $stockActionData[$r->id] = [
                'requester' => $r->requester_name,
                'nip' => $r->nip,
                'jabatan' => $r->jabatan,
                'bidang' => $r->bidang,
                'lines' => $r->lines->map(function ($line) {
                    return [
                        'name' => $line->item->name ?? '—',
                        'category' => $line->item->category ?? '—',
                        'lokasi' => $line->item->lokasi ?? '—',
                        'volume' => $line->quantity,
                        'quantity' => (int) $line->quantity,
                        'unit' => $line->item->unit ?? '',
                    ];
                })->values()->all(),
            ];
        }
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
    @endphp
    <div class="animate-fade-in">
        <!-- Header Actions Wrapper -->
        <div class="header-action-wrapper d-none">
            <div class="section-header-actions">
                <form method="GET" action="{{ route('stuff-requests.index') }}">
                    <div class="action-row-1 strreq-filter-row">
                        <div class="strreq-search-row d-flex align-items-center gap-2" style="flex-wrap: nowrap !important;">
                            <div class="position-relative" id="stuffRequestsSearchWrapper" style="flex: 1 1 auto !important; width: auto !important; min-width: 0 !important;">
                                <input type="text"
                                    id="stuffRequestsSearchInput"
                                    class="form-control form-control-sm"
                                    name="search"
                                    value="{{ request('search') }}"
                                    autocomplete="off"
                                    placeholder="Cari pemohon/barang...">
                                <div id="stuffRequestsSearchSuggestions" class="autocomplete-suggestions" style="display:none;"></div>
                            </div>
                            <a href="{{ route('stuff-requests.index') }}" 
                                class="btn btn-reset btn-outline-reset" 
                                style="flex: 0 0 auto !important;"
                                title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                            </a>
                        </div>
                        
                        <div class="strreq-select-row">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                @unless($isTeknik)
                                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                @endunless
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Done</option>
                                <option value="cancel" {{ request('status') == 'cancel' ? 'selected' : '' }}>Cancel</option>
                            </select>
                            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Tahun</option>
                                @foreach($years as $yr)
                                    <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Bulan</option>
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ request('month') == $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-header">
                <span>
                    <i class="bi bi-inbox-fill text-primary-custom me-2"></i>Daftar Permintaan Barang
                    @if($pendingCount > 0)
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }} pending</span>
                    @endif
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-striped" id="stuff-requests-table">
                        <thead>
                            <tr>
                                <th style="width:45px;">No</th>
                                <th>Tanggal</th>
                                <th>NIP</th>
                                <th>Jabatan</th>
                                <th>Bidang</th>
                                <th>Nama Pemohon</th>
                                <th style="min-width:120px;">Jumlah Barang</th>
                                <th>Kebutuhan</th>
                                <th>Status</th>
                                <th class="text-center" style="width:72px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $index => $req)
                                <tr data-id="{{ $req->id }}"
                                    data-requester-name="{{ $req->requester_name }}"
                                    data-nip="{{ $req->nip }}"
                                    data-jabatan="{{ $req->jabatan }}"
                                    data-bidang="{{ $req->bidang }}"
                                    data-items="{{ $req->lines->map(fn($l) => $l->item->name ?? '')->filter()->implode(', ') }}">
                                    <td>{{ $requests->firstItem() + $index }}</td>
                                    <td style="white-space:nowrap;">{{ $req->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="fw-600">{{ $req->nip }}</td>
                                    <td class="fw-600">{{ $req->jabatan }}</td>
                                    <td class="fw-600">{{ $req->bidang }}</td>
                                    <td class="fw-600">{{ $req->requester_name }}</td>
                                    <td>
                                        @if($req->lines->isEmpty())
                                            <span style="color:var(--text-muted);font-size:13px;">—</span>
                                        @else
                                            <span class="fw-600" style="font-size:13px;">{{ $req->lines->count() }} jenis</span>
                                        @endif
                                    </td>
                                    <td style="max-width:200px;" class="cell-ellipsis">
                                        @if($req->notes)
                                            <span
                                                style="font-size:12px;color:var(--text-secondary);">{{ Str::limit($req->notes, 50) }}</span>
                                        @else
                                            <span style="color:var(--text-muted);font-size:12px;">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center" style="vertical-align: middle;">
                                        @if($req->status === 'pending')
                                            <span class="badge-status"
                                                style="background:var(--warning-bg);color:var(--warning-dark);">
                                                <i class="bi bi-clock-fill"></i> Pending
                                            </span>
                                        @elseif($req->status === 'approved')
                                            <span class="badge-status" style="background:var(--success-bg);color:var(--success);">
                                                <i class="bi bi-check-circle-fill"></i> Approved
                                            </span>
                                            @if($req->processor)
                                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                                    oleh {{ $req->processor->name }}
                                                </div>
                                            @endif
                                        @elseif($req->status === 'completed')
                                            <span class="badge-status" style="background:#d1fae5;color:#065f46;">
                                                <i class="bi bi-check-lg"></i> Done
                                            </span>
                                            @if($req->completer)
                                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                                    oleh {{ $req->completer->name }}
                                                </div>
                                            @endif
                                        @elseif($req->status === 'cancel')
                                            <span class="badge-status" style="background:#fef2f2;color:#991b1b;">
                                                <i class="bi bi-slash-circle-fill"></i> Cancel
                                            </span>
                                            @if($req->completer)
                                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                                    oleh {{ $req->completer->name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="badge-status" style="background:var(--danger-bg);color:var(--danger);">
                                                <i class="bi bi-x-circle-fill"></i> Rejected
                                            </span>
                                            @if($req->processor)
                                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                                    oleh {{ $req->processor->name }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php $hasAction = false; @endphp
                                        @if($req->status === 'pending' && $canProcessAsAdmin && $isTeknik)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-stuff-request-modal-open"
                                                title="Rincian barang & selesai"
                                                data-req-id="{{ $req->id }}"
                                                data-scenario="admin_teknik_pending"
                                                data-form-complete="#completeReq-{{ $req->id }}"
                                                data-form-cancel="#cancelReq-{{ $req->id }}">
                                                <i class="bi bi-box-seam"></i>
                                            </button>
                                            @php $hasAction = true; @endphp
                                            <div class="d-none" aria-hidden="true">
                                                <form method="POST" action="{{ route('stuff-requests.complete', $req) }}"
                                                    id="completeReq-{{ $req->id }}">@csrf</form>
                                                <form method="POST" action="{{ route('stuff-requests.cancel', $req) }}"
                                                    id="cancelReq-{{ $req->id }}">@csrf</form>
                                            </div>
                                        @elseif($req->status === 'pending' && $canProcessAsAdmin)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-stuff-request-modal-open"
                                                title="Rincian barang & tindakan"
                                                data-req-id="{{ $req->id }}"
                                                data-scenario="admin_pending"
                                                data-form-approve="#approveReq-{{ $req->id }}"
                                                data-form-reject="#rejectReq-{{ $req->id }}">
                                                <i class="bi bi-box-seam"></i>
                                            </button>
                                            @php $hasAction = true; @endphp
                                            <div class="d-none" aria-hidden="true">
                                                <form method="POST" action="{{ route('stuff-requests.approve', $req) }}"
                                                    id="approveReq-{{ $req->id }}">@csrf</form>
                                                <form method="POST" action="{{ route('stuff-requests.reject', $req) }}"
                                                    id="rejectReq-{{ $req->id }}">@csrf</form>
                                            </div>
                                        @elseif($req->status === 'approved' && auth()->user()->isStaff())
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-stuff-request-modal-open"
                                                title="Rincian barang & tindakan"
                                                data-req-id="{{ $req->id }}"
                                                data-scenario="staff_approved"
                                                data-form-complete="#completeReq-{{ $req->id }}"
                                                data-form-cancel="#cancelReq-{{ $req->id }}">
                                                <i class="bi bi-box-seam"></i>
                                            </button>
                                            @php $hasAction = true; @endphp
                                            <div class="d-none" aria-hidden="true">
                                                <form method="POST" action="{{ route('stuff-requests.complete', $req) }}"
                                                    id="completeReq-{{ $req->id }}">@csrf</form>
                                                <form method="POST" action="{{ route('stuff-requests.cancel', $req) }}"
                                                    id="cancelReq-{{ $req->id }}">@csrf</form>
                                            </div>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-outline-danger-strong"
                                                title="Hapus Riwayat"
                                                onclick="swalConfirm('Hapus Riwayat?', 'Riwayat permintaan barang dari {{ $req->requester_name }} akan dihapus. Data masih bisa dipulihkan oleh developer bila diperlukan.', 'warning', 'Ya, Hapus', '#deleteReq-{{ $req->id }}')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            @php $hasAction = true; @endphp
                                            <form action="{{ route('stuff-requests.destroy', $req) }}" method="POST" id="deleteReq-{{ $req->id }}" class="d-none">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endif

                                        @if(!$hasAction)
                                            <span style="font-size:11px;color:var(--text-muted);">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="10">
                                        <i class="bi bi-inbox"
                                            style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Tidak ada Permintaan Barang
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($requests->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $requests->links('pagination.custom') }}
            </div>
        @endif
    </div>

    {{-- Modal: rincian barang + Approve / Reject / Done / Cancel --}}
    <div class="modal fade inventrack-modal" id="stuffRequestItemsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stuffRequestItemsModalTitle">
                        <i class="bi bi-box-seam me-2"></i><span id="stuffRequestItemsModalTitleText">Rincian Permintaan Barang</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="rounded-3 p-3 mb-3" style="background:var(--primary-bg, rgba(99,102,241,0.08));font-size:13px;">
                        <div class="row g-2">
                            <div class="col-sm-6"><span class="text-muted">Pemohon</span><br><strong id="stockActionModalRequester">—</strong></div>
                            <div class="col-sm-6"><span class="text-muted">NIP</span><br><strong id="stockActionModalNip">—</strong></div>
                            <div class="col-sm-6"><span class="text-muted">Jabatan</span><br><span id="stockActionModalJabatan">—</span></div>
                            <div class="col-sm-6"><span class="text-muted">Bidang</span><br><span id="stockActionModalBidang">—</span></div>
                        </div>
                    </div>
                    <p class="small text-muted mb-2" id="stockActionModalHint">Barang yang diminta:</p>
                    <div class="table-responsive rounded-3 border" style="border-color:var(--border-color, #dee2e6) !important;">
                        <table class="table table-sm mb-0 align-middle">
                            <thead style="background:var(--table-header-bg, #f8fafc);">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Nama Barang</th>
                                    <th>{{ $isTeknik ? 'Komponen' : 'Kategori' }}</th>
                                    @if($isTeknik)
                                        <th>Lokasi</th>
                                        <th class="text-center">Volume (Jumlah)</th>
                                    @else
                                        <th class="Center">Jumlah</th>
                                    @endif
                                    <th>Satuan</th>
                                </tr>
                            </thead>
                            <tbody id="stockActionModalLinesBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2 justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <div class="d-flex flex-wrap gap-2 justify-content-end" id="stuffRequestModalActionGroup">
                        <button type="button" class="btn btn-success d-none" id="stockActionModalBtnApprove">
                            <i class="bi bi-check-lg me-1"></i>Setujui
                        </button>
                        <button type="button" class="btn btn-danger d-none" id="stockActionModalBtnReject">
                            <i class="bi bi-x-lg me-1"></i>Tolak
                        </button>
                        <button type="button" class="btn btn-success d-none" id="stockActionModalBtnComplete">
                            <i class="bi bi-check2-all me-1"></i>Selesai (Done)
                        </button>
                        <button type="button" class="btn btn-danger d-none" id="stockActionModalBtnCancel">
                            <i class="bi bi-slash-circle me-1"></i>Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.__stockActionData = @json($stockActionData);
        const isTeknikStuffRequest = @json($isTeknik);

        (function () {
            const modalEl = document.getElementById('stuffRequestItemsModal');
            if (!modalEl) return;

            const modal = new bootstrap.Modal(modalEl);
            const titleTextEl = document.getElementById('stuffRequestItemsModalTitleText');
            const tbody = document.getElementById('stockActionModalLinesBody');
            const btnApprove = document.getElementById('stockActionModalBtnApprove');
            const btnReject = document.getElementById('stockActionModalBtnReject');
            const btnComplete = document.getElementById('stockActionModalBtnComplete');
            const btnCancel = document.getElementById('stockActionModalBtnCancel');

            let formApproveSel = null;
            let formRejectSel = null;
            let formCompleteSel = null;
            let formCancelSel = null;

            function formatQty(q, unit) {
                const n = new Intl.NumberFormat('id-ID').format(q);
                return unit ? (n + ' ' + unit) : n;
            }

            function hideAllActionButtons() {
                [btnApprove, btnReject, btnComplete, btnCancel].forEach(function (b) {
                    b.classList.add('d-none');
                });
            }

            function submitFormBySelector(sel) {
                if (!sel) return;
                const form = document.querySelector(sel);
                if (form) form.submit();
                modal.hide();
            }

            document.querySelectorAll('.btn-stuff-request-modal-open').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-req-id');
                    const scenario = this.getAttribute('data-scenario');
                    const data = window.__stockActionData && window.__stockActionData[id];

                    if (!data) return;

                    formApproveSel = this.getAttribute('data-form-approve');
                    formRejectSel = this.getAttribute('data-form-reject');
                    formCompleteSel = this.getAttribute('data-form-complete');
                    formCancelSel = this.getAttribute('data-form-cancel');

                    document.getElementById('stockActionModalRequester').textContent = data.requester || '—';
                    document.getElementById('stockActionModalNip').textContent = data.nip || '—';
                    document.getElementById('stockActionModalJabatan').textContent = data.jabatan || '—';
                    document.getElementById('stockActionModalBidang').textContent = data.bidang || '—';

                    tbody.replaceChildren();
                    (data.lines || []).forEach(function (line, idx) {
                        const tr = document.createElement('tr');
                        const tdNo = document.createElement('td');
                        tdNo.textContent = String(idx + 1);
                        const tdName = document.createElement('td');
                        tdName.className = 'fw-600';
                        tdName.textContent = line.name || '—';
                        const tdCat = document.createElement('td');
                        tdCat.style.color = 'var(--text-secondary)';
                        tdCat.textContent = line.category || '—';
                        tr.appendChild(tdNo);
                        tr.appendChild(tdName);
                        tr.appendChild(tdCat);
                        if (isTeknikStuffRequest) {
                            const tdLokasi = document.createElement('td');
                            tdLokasi.textContent = line.lokasi || 'â€”';
                            const tdVolume = document.createElement('td');
                            tdVolume.className = 'text-center fw-700';
                            tdVolume.style.color = 'var(--primary)';
                            tdVolume.textContent = new Intl.NumberFormat('id-ID').format(line.quantity || 0);
                            const tdUnit = document.createElement('td');
                            tdUnit.textContent = line.unit || '';
                            tr.appendChild(tdLokasi);
                            tr.appendChild(tdVolume);
                            tr.appendChild(tdUnit);
                        } else {
                            const tdQty = document.createElement('td');
                            tdQty.className = 'text-end fw-700';
                            tdQty.style.color = 'var(--primary)';
                            tdQty.textContent = formatQty(line.quantity, line.unit);
                            const tdUnit = document.createElement('td');
                            tdUnit.textContent = line.unit || '';
                            tr.appendChild(tdQty);
                            tr.appendChild(tdUnit);
                        }
                        tbody.appendChild(tr);
                    });

                    hideAllActionButtons();
                    if (scenario === 'admin_teknik_pending') {
                        titleTextEl.textContent = 'Rincian permintaan - tindakan admin Teknik';
                        btnComplete.classList.remove('d-none');
                        btnCancel.classList.remove('d-none');
                    } else if (scenario === 'admin_pending') {
                        titleTextEl.textContent = 'Rincian permintaan — persetujuan admin';
                        btnApprove.classList.remove('d-none');
                        btnReject.classList.remove('d-none');
                    } else if (scenario === 'staff_approved') {
                        titleTextEl.textContent = 'Rincian permintaan — penyelesaian staf';
                        btnComplete.classList.remove('d-none');
                        btnCancel.classList.remove('d-none');
                    } else {
                        titleTextEl.textContent = 'Rincian Permintaan Barang';
                    }

                    modal.show();
                });
            });

            btnApprove.addEventListener('click', function () {
                submitFormBySelector(formApproveSel);
            });
            btnReject.addEventListener('click', function () {
                submitFormBySelector(formRejectSel);
            });
            btnComplete.addEventListener('click', function () {
                submitFormBySelector(formCompleteSel);
            });
            btnCancel.addEventListener('click', function () {
                submitFormBySelector(formCancelSel);
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                hideAllActionButtons();
                formApproveSel = formRejectSel = formCompleteSel = formCancelSel = null;
            });
        })();

        // Autocomplete search suggestions for Stuff Requests
        (function() {
            const searchInput = document.getElementById('stuffRequestsSearchInput');
            const suggestionsBox = document.getElementById('stuffRequestsSearchSuggestions');
            const searchWrapper = document.getElementById('stuffRequestsSearchWrapper');
            const autocompleteUrl = @json(route('api.autocomplete.stuff-requests'));

            if (!searchInput || !suggestionsBox || !searchWrapper) return;

            let debounceTimer = null;

            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim();
                if (!keyword) {
                    suggestionsBox.style.display = 'none';
                    suggestionsBox.innerHTML = '';
                    return;
                }

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const params = new URLSearchParams({ q: keyword });

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
                    suggestionsBox.innerHTML = '<div class="autocomplete-no-result">Tidak ada permintaan ditemukan</div>';
                    suggestionsBox.style.display = 'block';
                    return;
                }

                suggestionsBox.innerHTML = items.map(item => `
                    <div class="autocomplete-item" data-name="${item.name}">
                        <div class="autocomplete-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="autocomplete-content">
                            <div class="autocomplete-title">${item.name} (${item.nip})</div>
                            <div class="autocomplete-subtitle">
                                ${item.jabatan} • ${item.bidang} • ${item.items || '-'}
                            </div>
                        </div>
                    </div>
                `).join('');

                suggestionsBox.style.display = 'block';

                suggestionsBox.querySelectorAll('.autocomplete-item').forEach(el => {
                    el.addEventListener('click', function() {
                        searchInput.value = this.dataset.name;
                        suggestionsBox.style.display = 'none';
                        searchInput.closest('form').submit();
                    });
                });
            }

            document.addEventListener('click', function(e) {
                if (searchWrapper && !searchWrapper.contains(e.target)) {
                    suggestionsBox.style.display = 'none';
                }
            });

            searchInput.addEventListener('focus', function() {
                if (this.value.trim() !== '' && suggestionsBox.innerHTML.trim() !== '') {
                    suggestionsBox.style.display = 'block';
                }
            });
        })();
    </script>
@endpush
