@extends('layouts.app')

@section('title', 'Stok Request')
@section('subtitle', 'Riwayat dan approval request stok')

@section('content')
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
        $exportParams = request()->except('page');
        $stockRequestDetailData = [];

        foreach ($stockRequests as $requestRow) {
            $stockRequestDetailData[$requestRow->id] = [
                'requester' => $requestRow->user->name ?? '-',
                'date' => $requestRow->created_at->format('d/m/Y H:i'),
                'status' => ucfirst($requestRow->status),
                'category' => $requestRow->category ?: ($requestRow->lines->first()?->category ?? '-'),
                'grand_total' => (int) $requestRow->lines->sum(fn($line) => (int) $line->price * (int) $line->quantity),
                'lines' => $requestRow->lines->map(function ($line) {
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
                        'description' => $line->description ?: '-',
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
                <form method="GET" action="{{ route('stock-requests.index') }}">
                    <div class="action-row-1 sr-filter-row">
                        <div class="sr-select-row">
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
                        
                        <div class="sr-select-row">
                            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="sr-action-row d-flex align-items-center gap-2" style="flex-wrap: nowrap !important;">
                             <a href="{{ route('stock-requests.index') }}" 
                                class="btn btn-reset btn-outline-reset" 
                                style="flex: 0 0 auto !important;"
                                title="Reset Filter">
                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                            </a>
                            <a href="{{ route('stock-requests.export', $exportParams) }}" 
                                class="btn btn-success btn-sm w-100"
                                style="flex: 1 1 auto !important; height: 38px !important; display: inline-flex !important; align-items: center !important; justify-content: center !important;">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex-wrap gap-2">
                <span>
                    <i class="bi bi-cart-check-fill text-primary-custom me-2"></i>Daftar Stok Request
                    @if(((auth()->user()->isAdmin() && !$isTeknik) || (auth()->user()->isManager() && $isTeknik)) && $pendingCount > 0)
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingCount }} pending</span>
                    @endif
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table" id="stock-requests-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Tanggal</th>
                                <th>Pemohon</th>
                                <th>Kategori</th>
                                <th>Barang</th>
                                <th class="text-center">Status</th>
                                <th>Diproses Oleh</th>
                                <th class="text-center" style="width:72px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stockRequests as $index => $stockRequest)
                                <tr>
                                    <td>{{ $stockRequests->firstItem() + $index }}</td>
                                    <td style="white-space:nowrap;">{{ $stockRequest->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="fw-600">{{ $stockRequest->user->name ?? '-' }}</td>
                                    <td>
                                        <span>
                                            {{ $stockRequest->category ?: ($stockRequest->lines->first()?->category ?? '-') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($stockRequest->lines->isEmpty())
                                            <span class="text-muted" style="font-size:12px;">-</span>
                                        @else
                                            <span class="fw-600" style="font-size:13px;">{{ $stockRequest->lines->count() }} jenis barang</span>
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::limit($stockRequest->lines->pluck('item.name')->filter()->join(', '), 48) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-status stock-request-status stock-request-status-{{ $stockRequest->status }}">
                                            {{ ucfirst($stockRequest->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $stockRequest->processor->name ?? '-' }}
                                        @if($stockRequest->processed_at)
                                            <div class="small text-muted">{{ $stockRequest->processed_at->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-stock-request-detail-open"
                                            title="Rincian barang"
                                            data-request-id="{{ $stockRequest->id }}"
                                            data-can-process="{{ ((auth()->user()->isAdmin() && !$isTeknik) || (auth()->user()->isManager() && $isTeknik)) && $stockRequest->status === 'pending' ? '1' : '0' }}"
                                            data-form-approve="#approveStockReq-{{ $stockRequest->id }}"
                                            data-form-reject="#rejectStockReq-{{ $stockRequest->id }}">
                                            <i class="bi bi-box-seam"></i>
                                        </button>
                                        @if(((auth()->user()->isAdmin() && !$isTeknik) || (auth()->user()->isManager() && $isTeknik)) && $stockRequest->status === 'pending')
                                            <div class="d-none" aria-hidden="true">
                                                <form action="{{ route('stock-requests.approve', $stockRequest) }}" method="POST" id="approveStockReq-{{ $stockRequest->id }}">
                                                    @csrf
                                                </form>
                                                <form action="{{ route('stock-requests.reject', $stockRequest) }}" method="POST" id="rejectStockReq-{{ $stockRequest->id }}">
                                                    @csrf
                                                </form>
                                            </div>
                                        @endif
                                        @if(auth()->user()->isSuperAdmin())
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-outline-danger-strong"
                                                title="Hapus Riwayat"
                                                onclick="swalConfirm('Hapus Riwayat?', 'Riwayat stock request ini akan dihapus. Data masih bisa dipulihkan oleh developer bila diperlukan.', 'warning', 'Ya, Hapus', '#deleteStockReq-{{ $stockRequest->id }}')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                            <form action="{{ route('stock-requests.destroy', $stockRequest) }}" method="POST" id="deleteStockReq-{{ $stockRequest->id }}" class="d-none">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="no-data-row">
                                    <td colspan="9">
                                        <i class="bi bi-inbox" style="font-size:40px;display:block;margin-bottom:8px;opacity:0.3;"></i>
                                        Belum ada stok request
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($stockRequests->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $stockRequests->links('pagination.custom') }}
            </div>
        @endif
    </div>

    <div class="modal fade inventrack-modal" id="stockRequestDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-box-seam me-2"></i><span>Rincian Stok Request</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="rounded-3 p-3 mb-3" style="background:var(--primary-bg);font-size:13px;">
                        <div class="row g-2">
                            <div class="col-sm-6"><span class="text-muted">Pemohon</span><br><strong id="stockRequestDetailRequester">-</strong></div>
                            <div class="col-sm-6"><span class="text-muted">Tanggal</span><br><strong id="stockRequestDetailDate">-</strong></div>
                            <div class="col-sm-6"><span class="text-muted">Kategori Request</span><br><span id="stockRequestDetailCategory">-</span></div>
                            <div class="col-sm-6"><span class="text-muted">Status</span><br><span id="stockRequestDetailStatus">-</span></div>
                        </div>
                    </div>
                    <div class="table-responsive rounded-3 border" style="border-color:var(--border-color) !important;">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                @if($isTeknik)
                                    <tr>
                                        <th style="width:44px;">No</th>
                                        <th style="min-width:140px;">No Normalisasi</th>
                                        <th style="min-width:180px;">Barang</th>
                                        <th style="min-width:140px;">Komponen</th>
                                        <th style="min-width:120px;">Ship Unloader</th>
                                        <th style="min-width:140px;">Lokasi</th>
                                        <th class="text-end" style="width:120px;white-space:nowrap;">Volume</th>
                                        <th class="text-end" style="min-width:160px;white-space:nowrap;">Harga Satuan</th>
                                        <th class="text-end" style="min-width:190px;white-space:nowrap;">Total Harga</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th style="width:44px;">No</th>
                                        <th style="min-width:180px;">Barang</th>
                                        <th style="min-width:140px;">Kategori</th>
                                        <th class="text-end" style="width:120px;">Jumlah</th>
                                        <th class="text-end" style="width:150px;">Harga Satuan</th>
                                        <th class="text-end" style="width:150px;">Total Harga</th>
                                        <th style="min-width:180px;">Keterangan</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody id="stockRequestDetailLinesBody"></tbody>
                        </table>
                    </div>
                    <div class="stock-request-grand-total mt-3">
                        <span>Total Harga Keseluruhan</span>
                        <strong id="stockRequestDetailGrandTotal">Rp 0</strong>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2 justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-success d-none" id="stockRequestDetailBtnApprove">
                            <i class="bi bi-check-lg me-1"></i>Approve
                        </button>
                        <button type="button" class="btn btn-danger d-none" id="stockRequestDetailBtnReject">
                            <i class="bi bi-x-lg me-1"></i>Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.__stockRequestDetailData = @json($stockRequestDetailData);
        const isTeknikStockRequest = @json($isTeknik);

        (function () {
            const modalEl = document.getElementById('stockRequestDetailModal');
            if (!modalEl) return;

            const modal = new bootstrap.Modal(modalEl);
            const tbody = document.getElementById('stockRequestDetailLinesBody');
            const btnApprove = document.getElementById('stockRequestDetailBtnApprove');
            const btnReject = document.getElementById('stockRequestDetailBtnReject');
            let approveFormSelector = null;
            let rejectFormSelector = null;

            function formatNumber(value) {
                return new Intl.NumberFormat('id-ID').format(value || 0);
            }

            function formatCurrency(value) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value || 0);
            }

            function setText(id, value) {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '-';
            }

            function hideActions() {
                btnApprove.classList.add('d-none');
                btnReject.classList.add('d-none');
            }

            function submitForm(selector) {
                const form = selector ? document.querySelector(selector) : null;
                if (!form) return;

                btnApprove.disabled = true;
                btnReject.disabled = true;
                form.submit();
            }

            document.querySelectorAll('.btn-stock-request-detail-open').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-request-id');
                    const data = window.__stockRequestDetailData && window.__stockRequestDetailData[id];
                    if (!data) return;

                    approveFormSelector = this.getAttribute('data-form-approve');
                    rejectFormSelector = this.getAttribute('data-form-reject');

                    setText('stockRequestDetailRequester', data.requester);
                    setText('stockRequestDetailDate', data.date);
                    setText('stockRequestDetailCategory', data.category);
                    setText('stockRequestDetailStatus', data.status);

                    tbody.replaceChildren();
                    (data.lines || []).forEach(function (line, index) {
                        const tr = document.createElement('tr');
                        if (isTeknikStockRequest) {
                            tr.innerHTML = `
                                <td>${index + 1}</td>
                                <td class="fw-600"></td>
                                <td class="fw-600"></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="text-end fw-700" style="color:var(--primary);"></td>
                                <td class="text-end"></td>
                                <td class="text-end fw-700"></td>
                            `;
                            tr.children[1].textContent = line.no_normalisasi || '-';
                            tr.children[2].textContent = line.name || '-';
                            tr.children[3].textContent = line.category || '-';
                            tr.children[4].textContent = line.ship_unloader || '-';
                            tr.children[5].textContent = line.lokasi || '-';
                            tr.children[6].textContent = formatNumber(line.quantity || 0) + (line.unit ? ' ' + line.unit : '');
                            tr.children[7].textContent = formatCurrency(line.price);
                            tr.children[8].textContent = formatCurrency(line.line_total);
                        } else {
                            tr.innerHTML = `
                                <td>${index + 1}</td>
                                <td class="fw-600"></td>
                                <td></td>
                                <td class="text-end fw-700" style="color:var(--primary);white-space:nowrap;"></td>
                                <td class="text-end" style="white-space:nowrap;"></td>
                                <td class="text-end fw-700" style="white-space:nowrap;"></td>
                                <td style="color:var(--text-secondary);"></td>
                            `;
                            tr.children[1].textContent = line.name || '-';
                            tr.children[2].textContent = line.category || '-';
                            tr.children[3].textContent = formatNumber(line.quantity) + (line.unit ? ' ' + line.unit : '');
                            tr.children[4].textContent = formatCurrency(line.price);
                            tr.children[5].textContent = formatCurrency(line.line_total);
                            tr.children[6].textContent = line.description || '-';
                        }
                        tbody.appendChild(tr);
                    });

                    setText('stockRequestDetailGrandTotal', formatCurrency(data.grand_total));

                    hideActions();
                    if (this.getAttribute('data-can-process') === '1') {
                        btnApprove.classList.remove('d-none');
                        btnReject.classList.remove('d-none');
                    }

                    modal.show();
                });
            });

            btnApprove.addEventListener('click', function () {
                submitForm(approveFormSelector);
            });

            btnReject.addEventListener('click', function () {
                submitForm(rejectFormSelector);
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                hideActions();
                approveFormSelector = null;
                rejectFormSelector = null;
                tbody.replaceChildren();
            });
        })();
    </script>
@endpush
