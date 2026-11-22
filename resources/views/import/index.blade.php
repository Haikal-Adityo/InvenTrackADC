@extends('layouts.app')

@section('title', 'Import Data')
@section('subtitle', 'Import data dari file Excel ke database')

@section('content')
    @php
        $isTeknik = auth()->user()->bidang === 'teknik';
    @endphp

    <div class="animate-fade-in">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                {{-- Import Result --}}
                @if(session('success'))
                    <div class="card mb-4" style="border-left: 4px solid var(--primary); overflow: hidden;">
                        <div class="card-body">
                            <div class="d-flex align-items-start gap-3">
                                <div
                                    style="width:42px;height:42px;background:var(--success-bg);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="bi bi-check-circle-fill" style="color:var(--success);font-size:20px;"></i>
                                </div>
                                <div>
                                    <h6 style="font-weight:700;margin-bottom:4px;color:var(--text-primary);">Import
                                        {{ session('import_type', 'Data') }} Berhasil
                                    </h6>
                                    <p style="margin:0;font-size:13px;color:var(--text-secondary);">{{ session('success') }}</p>
                                    @if(session('import_batch'))
                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2" style="font-size:12.5px;color:var(--text-secondary);">
                                            <span>Kode import: <strong>{{ session('import_batch') }}</strong>. Salah file / tanggal keliru?</span>
                                            <form method="POST" action="{{ route('import.undo', session('import_batch')) }}" id="undoImportForm">
                                                @csrf @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="swalConfirm('Batalkan import ini?', '{{ session('import_batch_count') }} transaksi pending dari import ini akan dihapus.', 'warning', 'Ya, Batalkan', '#undoImportForm')">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Batalkan Import Ini
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('import_failures'))
                    <div class="card mb-4" style="border-left: 4px solid var(--warning); overflow: hidden;">
                        <div class="card-body">
                            <h6 style="font-weight:700;margin-bottom:10px;color:var(--warning-dark);">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Beberapa baris gagal validasi
                            </h6>
                            <div class="table-container">
                                <table class="table" style="font-size:12px;">
                                    <thead>
                                        <tr>
                                            <th>Baris</th>
                                            <th>Kolom</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(session('import_failures') as $failure)
                                            @php
                                                $failureRow = is_array($failure) ? ($failure['row'] ?? '-') : $failure->row();
                                                $failureAttribute = is_array($failure) ? ($failure['attribute'] ?? '-') : $failure->attribute();
                                                $failureErrors = is_array($failure) ? ($failure['errors'] ?? []) : $failure->errors();
                                            @endphp
                                            <tr>
                                                <td><span class="badge-status"
                                                        style="background:var(--danger-bg);color:var(--danger);">{{ $failureRow }}</span>
                                                </td>
                                                <td><code style="font-size:11px;">{{ $failureAttribute }}</code></td>
                                                <td>{{ implode(', ', (array) $failureErrors) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Import Form --}}
                <div class="card">
                    <div class="card-header">
                        <span><i class="bi bi-cloud-arrow-up-fill text-primary-custom me-2"></i>Import Data Excel</span>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('import.process') }}" enctype="multipart/form-data"
                            id="importForm">
                            @csrf

                            {{-- Type Selection --}}
                            <div class="mb-4">
                                <label class="form-label">Tipe Data <span class="text-danger">*</span></label>
                                <div class="d-flex gap-3">
                                    <label class="import-type-card" id="typeItemsCard">
                                        <input type="radio" name="type" value="items" class="d-none" {{ old('type') === 'items' ? 'checked' : '' }}>
                                        <div class="import-type-inner">
                                            <div class="import-type-icon"
                                                style="background:var(--primary-bg);color:var(--primary);">
                                                <i class="bi bi-box-fill"></i>
                                            </div>
                                            <div>
                                                <div class="import-type-title">Master Barang</div>
                                                <div class="import-type-desc">{{ $isTeknik ? 'No normalisasi, komponen, lokasi' : 'Nama, kategori, satuan, min stok' }}</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="import-type-card" id="typeTransactionsCard">
                                        <input type="radio" name="type" value="transactions" class="d-none" {{ old('type') === 'transactions' ? 'checked' : '' }}>
                                        <div class="import-type-inner">
                                            <div class="import-type-icon"
                                                style="background:var(--info-bg);color:var(--info);">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </div>
                                            <div>
                                                <div class="import-type-title">Transaksi</div>
                                                <div class="import-type-desc">{{ $isTeknik ? 'Goods receipt/issue teknik' : 'Tanggal, barang, jenis, jumlah' }}</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                @error('type')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- File Upload --}}
                            <div class="mb-4">
                                <label class="form-label">File Excel <span class="text-danger">*</span></label>
                                <div class="upload-zone" id="uploadZone"
                                    onclick="document.getElementById('fileInput').click();">
                                    <input type="file" name="file" id="fileInput" accept=".xlsx,.xls,.csv" class="d-none">
                                    <div class="upload-zone-content" id="uploadContent">
                                        <div class="upload-zone-icon">
                                            <i class="bi bi-cloud-arrow-up"></i>
                                        </div>
                                        <div class="upload-zone-text">
                                            <strong>Klik untuk pilih file</strong> atau drag & drop
                                        </div>
                                        <div class="upload-zone-hint">
                                            Format: .xlsx, .xls, .csv (maks. 5MB)
                                        </div>
                                    </div>
                                    <div class="upload-zone-file d-none" id="uploadFile">
                                        <i class="bi bi-file-earmark-spreadsheet-fill"
                                            style="font-size:32px;color:var(--primary);"></i>
                                        <div>
                                            <div class="upload-file-name" id="fileName"></div>
                                            <div class="upload-file-size" id="fileSize"></div>
                                        </div>
                                        <button type="button" class="btn-remove-file"
                                            onclick="event.stopPropagation(); removeFile();" title="Hapus file">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                @error('file')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Template Download --}}
                            <div class="mb-4 p-3"
                                style="background:var(--primary-bg);border-radius:var(--radius-sm);border:1px solid rgba(16,185,129,0.15);">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-info-circle-fill" style="color:var(--primary);"></i>
                                        <span style="font-size:13px;color:var(--text-primary);">Download template terlebih
                                            dahulu agar format sesuai.</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('import.template', 'items') }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Template Barang
                                        </a>
                                        <a href="{{ route('import.template', 'transactions') }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Template Transaksi
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- Submit --}}
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                    <i class="bi bi-cloud-arrow-up"></i> Import Data
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Format Guide --}}
                <div class="card mt-4">
                    <div class="card-header">
                        <span><i class="bi bi-book-fill text-primary-custom me-2"></i>Panduan Format</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 style="font-weight:700;color:var(--text-primary);margin-bottom:12px;">
                                    <i class="bi bi-box-fill text-primary-custom me-1"></i> Master Barang
                                </h6>
                                <div class="table-container">
                                    <table class="table" style="font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th>Kolom Header</th>
                                                <th>Keterangan</th>
                                                <th>Wajib</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>nama_barang</code></td>
                                                <td>Nama barang</td>
                                                <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                </td>
                                            </tr>
                                            @if($isTeknik)
                                                <tr>
                                                    <td><code>no_normalisasi</code></td>
                                                    <td>Nomor normalisasi barang teknik</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td><code>kategori</code></td>
                                                <td>Kategori barang</td>
                                                <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                </td>
                                            </tr>
                                            @if($isTeknik)
                                                <tr>
                                                    <td><code>komponen</code></td>
                                                    <td>Komponen barang teknik</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><code>volume</code></td>
                                                    <td>Volume barang master SOH</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><code>lokasi</code></td>
                                                    <td>Lokasi penyimpanan</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td><code>satuan</code></td>
                                                <td>Satuan (Pcs, Rim, dll)</td>
                                                <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><code>min_stok</code></td>
                                                <td>Minimum stok</td>
                                                <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 style="font-weight:700;color:var(--text-primary);margin-bottom:12px;">
                                    <i class="bi bi-arrow-left-right text-primary-custom me-1"></i> Transaksi
                                </h6>
                                <div class="table-container">
                                    <table class="table" style="font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th>Kolom Header</th>
                                                <th>Keterangan</th>
                                                <th>Wajib</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>tanggal</code></td>
                                                <td>Format: dd/mm/yyyy</td>
                                                <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><code>nama_barang</code></td>
                                                <td>Harus sudah ada di master</td>
                                                <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                </td>
                                            </tr>
                                            @if($isTeknik)
                                                <tr>
                                                    <td><code>no_normalisasi</code></td>
                                                    <td>Nomor seri barang teknik</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td><code>jenis</code></td>
                                                <td>{{ $isTeknik ? 'in untuk Goods Receipt, out untuk Goods Issue' : 'in / out' }}</td>
                                                <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                </td>
                                            </tr>
                                            @if($isTeknik)
                                                <tr>
                                                    <td><code>ship_unloader</code></td>
                                                    <td>Nomor ship unloader, pisahkan dengan koma</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><code>lokasi</code></td>
                                                    <td>Lokasi transaksi/barang</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><code>jumlah</code></td>
                                                    <td>Jumlah transaksi goods receipt/issued</td>
                                                    <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                    </td>
                                                </tr>
                                            @endif
                                            @unless($isTeknik)
                                                <tr>
                                                    <td><code>jumlah</code></td>
                                                    <td>Jumlah (angka)</td>
                                                    <td><span class="badge-status" style="color:var(--success);">Ya</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><code>harga</code></td>
                                                    <td>Harga satuan</td>
                                                    <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                    </td>
                                                </tr>
                                            @endunless
                                            <tr>
                                                <td><code>keterangan</code></td>
                                                <td>Catatan</td>
                                                <td><span class="badge-status" style="color:var(--warning);">Opsional</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Type selection
        document.querySelectorAll('.import-type-card input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function () {
                document.querySelectorAll('.import-type-card').forEach(c => c.classList.remove('selected'));
                this.closest('.import-type-card').classList.add('selected');
                checkFormValid();
            });
            // Restore state on page load
            if (radio.checked) radio.closest('.import-type-card').classList.add('selected');
        });

        // File input
        const fileInput = document.getElementById('fileInput');
        const uploadZone = document.getElementById('uploadZone');
        const uploadContent = document.getElementById('uploadContent');
        const uploadFile = document.getElementById('uploadFile');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        fileInput.addEventListener('change', function () {
            if (this.files.length > 0) {
                showFile(this.files[0]);
            }
        });

        // Drag & drop
        uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('dragover'); });
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                showFile(e.dataTransfer.files[0]);
            }
        });

        function showFile(file) {
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            uploadContent.classList.add('d-none');
            uploadFile.classList.remove('d-none');
            checkFormValid();
        }

        function removeFile() {
            fileInput.value = '';
            uploadContent.classList.remove('d-none');
            uploadFile.classList.add('d-none');
            checkFormValid();
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1048576).toFixed(1) + ' MB';
        }

        function checkFormValid() {
            const typeSelected = document.querySelector('.import-type-card input:checked');
            const fileSelected = fileInput.files.length > 0;
            document.getElementById('submitBtn').disabled = !(typeSelected && fileSelected);
        }
    </script>
@endpush
