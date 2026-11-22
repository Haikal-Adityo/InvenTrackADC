@extends('layouts.app')

@section('title', 'Pengaturan PDF')
@section('subtitle', 'Teks PDF Form Permintaan (Rincian Stok Request)')

@section('content')
    @php
        $fields = [
            'title' => ['Judul Dokumen', 'Teks besar di kanan atas kop.'],
            'subtitle' => ['Sub-judul', 'Baris kedua di bawah judul (boleh dikosongkan).'],
            'verify_caption' => ['Label Verifikasi', 'Mis. "Diverifikasi,".'],
            'verify_role' => ['Jabatan Verifikator', 'Ditampilkan huruf kapital di kartu validasi kiri.'],
            'approve_caption' => ['Label Persetujuan', 'Mis. "Menyetujui,".'],
            'approve_role' => ['Jabatan Penyetuju', 'Ditampilkan huruf kapital di kartu validasi kanan.'],
            'footer' => ['Teks Footer', 'Catatan kecil di bagian bawah setiap halaman.'],
        ];
    @endphp
    <div class="animate-fade-in">
        <div class="mb-3">
            <a href="{{ route('stock-requests.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Stok Request
            </a>
        </div>

        <div class="card" style="max-width: 820px;">
            <div class="card-header flex-wrap gap-2">
                <span><i class="bi bi-file-earmark-pdf-fill text-primary-custom me-2"></i>PDF Form Permintaan</span>
                @if($sample)
                    <a href="{{ route('stock-requests.exportPdf', $sample) }}" target="_blank" class="btn btn-sm btn-outline-pdf">
                        <i class="bi bi-eye me-1"></i> Pratinjau ({{ $sample->document_number }})
                    </a>
                @endif
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('stock-requests.pdf-settings.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        @foreach($fields as $key => [$label, $hint])
                            <div class="{{ $key === 'footer' ? 'col-12' : 'col-md-6' }}">
                                <label class="form-label fw-600" for="pdf_{{ $key }}">{{ $label }}</label>
                                <input type="text" class="form-control @error($key) is-invalid @enderror" id="pdf_{{ $key }}" name="{{ $key }}"
                                    value="{{ old($key, $settings[$key]) }}" placeholder="{{ $defaults[$key] }}"
                                    maxlength="{{ $key === 'footer' ? 255 : 100 }}" {{ in_array($key, ['subtitle', 'footer'], true) ? '' : 'required' }}>
                                <div class="form-text">{{ $hint }} Bawaan: <em>{{ $defaults[$key] }}</em></div>
                                @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
