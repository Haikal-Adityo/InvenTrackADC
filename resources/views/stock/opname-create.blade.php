@extends('layouts.app')

@section('title', 'Stok Opname Baru')
@section('subtitle', 'Hitung fisik barang lalu isi stok aktual')

@section('content')
    <div class="animate-fade-in">
        <div class="mb-3">
            <a href="{{ route('stock.opname.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        </div>

        <form method="POST" action="{{ route('stock.opname.store') }}" id="soForm">
            @csrf
            <div class="card mb-3">
                <div class="card-body row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-600 mb-1">Tanggal & Jam SO</label>
                        <input type="datetime-local" name="counted_at" class="form-control form-control-sm" required
                            value="{{ old('counted_at', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-600 mb-1">Petugas SO</label>
                        <input type="text" class="form-control form-control-sm" value="{{ auth()->user()->name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-600 mb-1">Catatan (opsional)</label>
                        <input type="text" name="notes" class="form-control form-control-sm" maxlength="500" value="{{ old('notes') }}" placeholder="Mis. SO akhir bulan gudang ATK">
                    </div>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2" style="font-size:13px;">{{ $errors->first() }}</div>
            @endif

            <div class="card">
                <div class="card-header flex-wrap gap-2">
                    <span><i class="bi bi-clipboard-check text-primary-custom me-2"></i>Stok Opname</span>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <input type="search" id="soSearch" class="form-control form-control-sm" placeholder="Cari barang..." style="width:200px;">
                        <select id="soCategory" class="form-select form-select-sm" style="width:160px;">
                            <option value="">Semua Kategori</option>
                            @foreach($items->pluck('category')->unique()->sort() as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                        <div class="so-counters">
                            <span class="so-counter is-match" title="Cocok"><i class="bi bi-check-circle-fill"></i> <b id="soCountMatch">0</b></span>
                            <span class="so-counter is-minus" title="Kurang"><i class="bi bi-dash-circle-fill"></i> <b id="soCountMinus">0</b></span>
                            <span class="so-counter is-plus" title="Lebih"><i class="bi bi-plus-circle-fill"></i> <b id="soCountPlus">0</b></span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="px-3 py-2" style="font-size:12px;color:var(--text-secondary);background:var(--body-bg);">
                        Isi kolom <strong>Aktual</strong> hanya untuk barang yang dihitung. Barang yang dikosongkan tidak ikut stok opname.
                    </div>
                    <div class="table-container">
                        <table class="table mb-0" id="soTable">
                            <thead>
                                <tr>
                                    <th style="width:50px;">No</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th class="text-center">Stok</th>
                                    <th class="text-center" style="width:120px;">Aktual</th>
                                    <th class="text-center" style="width:90px;">Selisih</th>
                                    <th style="min-width:200px;">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $i => $item)
                                    <tr class="so-row" data-name="{{ strtolower($item['name']) }}" data-category="{{ $item['category'] }}" data-stock="{{ $item['stock'] }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-600">
                                            {{ $item['name'] }}
                                            <input type="hidden" name="lines[{{ $i }}][item_id]" value="{{ $item['id'] }}">
                                        </td>
                                        <td>{{ $item['category'] }}</td>
                                        <td>{{ $item['unit'] }}</td>
                                        <td class="text-center">{{ number_format($item['stock']) }}</td>
                                        <td><input type="number" min="0" step="1" name="lines[{{ $i }}][actual_stock]" class="form-control form-control-sm text-center so-actual" value="{{ old("lines.$i.actual_stock") }}"></td>
                                        <td class="text-center so-diff"><span class="text-muted">—</span></td>
                                        <td><input type="text" name="lines[{{ $i }}][notes]" maxlength="500" class="form-control form-control-sm" placeholder="Catatan..." value="{{ old("lines.$i.notes") }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span style="font-size:12.5px;color:var(--text-secondary);"><b id="soFilledCount">0</b> barang diisi</span>
                    <button type="submit" class="btn btn-primary" id="soSubmit" disabled>
                        <i class="bi bi-send-fill me-1"></i> Simpan & Ajukan Verifikasi
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('#soTable .so-row'));
            const search = document.getElementById('soSearch');
            const category = document.getElementById('soCategory');
            const submit = document.getElementById('soSubmit');

            function updateRow(row) {
                const input = row.querySelector('.so-actual');
                const cell = row.querySelector('.so-diff');
                if (input.value === '') {
                    cell.innerHTML = '<span class="text-muted">—</span>';
                    row.dataset.diff = '';
                    return;
                }
                const diff = parseInt(input.value, 10) - parseInt(row.dataset.stock, 10);
                row.dataset.diff = diff;
                cell.innerHTML = diff === 0
                    ? '<i class="bi bi-check-circle-fill text-success" title="Cocok"></i>'
                    : `<span class="fw-700 ${diff < 0 ? 'text-danger' : 'so-text-plus'}">${diff > 0 ? '+' : ''}${diff}</span>`;
            }

            function updateCounters() {
                const filled = rows.filter((r) => r.dataset.diff !== undefined && r.dataset.diff !== '');
                document.getElementById('soCountMatch').textContent = filled.filter((r) => Number(r.dataset.diff) === 0).length;
                document.getElementById('soCountMinus').textContent = filled.filter((r) => Number(r.dataset.diff) < 0).length;
                document.getElementById('soCountPlus').textContent = filled.filter((r) => Number(r.dataset.diff) > 0).length;
                document.getElementById('soFilledCount').textContent = filled.length;
                submit.disabled = filled.length === 0;
            }

            function applyFilter() {
                const q = search.value.trim().toLowerCase();
                const c = category.value;
                rows.forEach((r) => {
                    r.style.display = (!q || r.dataset.name.includes(q)) && (!c || r.dataset.category === c) ? '' : 'none';
                });
            }

            rows.forEach((row) => {
                row.querySelector('.so-actual').addEventListener('input', () => { updateRow(row); updateCounters(); });
                updateRow(row);
            });
            updateCounters();
            search.addEventListener('input', applyFilter);
            category.addEventListener('change', applyFilter);

            // Hanya kirim baris yang diisi (hindari batas max_input_vars PHP) + cegah submit ganda
            document.getElementById('soForm').addEventListener('submit', function (e) {
                if (submit.dataset.sending) { e.preventDefault(); return; }
                rows.forEach((r) => {
                    const empty = r.querySelector('.so-actual').value === '';
                    r.querySelectorAll('input').forEach((inp) => { inp.disabled = empty; });
                });
                submit.dataset.sending = '1';
                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';
            });
            // Kembali dari halaman (bfcache) → aktifkan input lagi
            window.addEventListener('pageshow', function () {
                rows.forEach((r) => r.querySelectorAll('input').forEach((inp) => { inp.disabled = false; }));
                delete submit.dataset.sending;
                updateCounters();
            });
        })();
    </script>
@endpush
