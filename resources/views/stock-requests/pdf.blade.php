<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rincian Stok Request #{{ $stockRequest->id }}</title>
    <style>
        @page { margin: 30px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9.5pt; color: #1a1a2e; line-height: 1.4; margin: 10px; padding: 10px; position: relative; }

        .page { padding: 10px; padding-bottom: 40px; }

        /* HEADER (pola kop surat VMS, warna brand Nextlog) */
        .header { width: 100%; margin-bottom: 16px; border-bottom: 3px solid #10b981; padding-bottom: 12px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-left { width: 42%; }
        .header-logo { width: 250px; height: auto; max-height: 62px; }
        .header-right { width: 58%; text-align: right; }
        .header-title-main { font-size: 14pt; color: #064e3b; font-weight: 700; letter-spacing: 0.5px; }
        .header-pm { font-size: 11pt; font-weight: 700; color: #3d4654; margin-top: -1px; }
        .header-number { font-size: 8.5pt; font-weight: 700; color: #059669; margin-top: 3px; }

        /* SECTION HEADING */
        .section-heading { font-size: 10.5pt; font-weight: 700; color: #064e3b; padding: 5px 0 5px 8px; border-left: 3px solid #10b981; margin: 14px 0 8px; }

        /* INFO (isi sama dengan kotak info di modal Rincian) */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; table-layout: fixed; }
        .info-table td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: middle; font-size: 9pt; }
        .info-table .label { font-weight: 700; background: #ecfdf5; color: #111827; width: 20%; }
        .info-table .value { color: #111827; width: 30%; }

        .status-badge { font-weight: 700; }
        .status-approved { color: #059669; }
        .status-pending { color: #d97706; }
        .status-rejected { color: #dc2626; }

        /* DATA TABLE */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 8.5pt; table-layout: fixed; }
        .data-table th { background: #f1f5f9; padding: 6px 6px; text-align: left; font-weight: 700; color: #374151; border: 1px solid #d1d5db; font-size: 7.5pt; text-transform: uppercase; }
        .data-table td { padding: 5px 6px; border: 1px solid #d1d5db; vertical-align: middle; word-wrap: break-word; }
        .data-table tr { page-break-inside: avoid; }
        .data-table.teknik { font-size: 8pt; }
        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .fw-700 { font-weight: 700; }
        .qty { font-weight: 700; color: #059669; }
        .muted { color: #6c757d; }

        /* GRAND TOTAL (seperti bar total di modal) */
        .grand-total-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; page-break-inside: avoid; }
        .grand-total-table td { padding: 9px 12px; border: 1px solid #a7f3d0; background: #ecfdf5; }
        .grand-total-table .gt-label { font-weight: 700; color: #374151; font-size: 9.5pt; border-right: none; }
        .grand-total-table .gt-value { font-weight: 700; color: #059669; text-align: right; font-size: 12pt; border-left: none; }

        /* FOOTER */
        .pdf-page-footer { position: fixed; bottom: 0; left: 0; right: 0; width: 100%; padding: 6px 10px 10px; background: #fff; text-align: center; }
        .note { text-align: center; font-size: 8pt; color: #6b7280; margin-top: 6px; font-style: italic; }
    </style>
</head>
<body>
@php
    $created = $stockRequest->created_at;
    $bulanRomawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    $docNo = 'ADC-' . $created->format('y') . $bulanRomawi[$created->month] . 'STR' . str_pad((string) $stockRequest->id, 3, '0', STR_PAD_LEFT);

    $rupiah = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $qty = fn ($line) => number_format((int) $line['quantity'], 0, ',', '.') . ($line['unit'] ? ' ' . $line['unit'] : '');

    $kategori = $stockRequest->category ?: ($lines->first()['category'] ?? '-');
    $status = $stockRequest->status;
@endphp

<div class="page">
    {{-- HEADER --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img class="header-logo" src="{{ public_path('images/logo-perusahaan.png') }}" alt="Logo Perusahaan">
                </td>
                <td class="header-right">
                    <div class="header-title-main">RINCIAN STOK REQUEST</div>
                    <div class="header-pm">BIDANG {{ strtoupper($stockRequest->bidang ?? '-') }}</div>
                    <div class="header-number">No. {{ $docNo }} | {{ $created->translatedFormat('d F Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- INFO --}}
    <div class="section-heading">Data Request</div>
    <table class="info-table">
        <tr>
            <td class="label">Pemohon</td>
            <td class="value fw-700">{{ $stockRequest->user->name ?? '-' }}</td>
            <td class="label">Tanggal</td>
            <td class="value fw-700">{{ $created->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Kategori Request</td>
            <td class="value">{{ $kategori }}</td>
            <td class="label">Status</td>
            <td class="value"><span class="status-badge status-{{ $status }}">{{ ucfirst($status) }}</span></td>
        </tr>
    </table>

    {{-- DETAIL BARANG --}}
    <div class="section-heading">Rincian Barang</div>
    @if($isTeknik)
        <table class="data-table teknik">
            <thead>
                <tr>
                    <th style="width:5%;" class="text-center">No</th>
                    <th style="width:12%;">No Normalisasi</th>
                    <th style="width:16%;">Barang</th>
                    <th style="width:11%;">Komponen</th>
                    <th style="width:10%;">Ship Unloader</th>
                    <th style="width:10%;">Lokasi</th>
                    <th style="width:10%;" class="text-end">Volume</th>
                    <th style="width:12%;" class="text-end">Harga Satuan</th>
                    <th style="width:14%;" class="text-end">Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $index => $line)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="fw-700">{{ $line['no_normalisasi'] }}</td>
                        <td class="fw-700">{{ $line['name'] }}</td>
                        <td>{{ $line['category'] }}</td>
                        <td>{{ $line['ship_unloader'] }}</td>
                        <td>{{ $line['lokasi'] }}</td>
                        <td class="text-end qty">{{ $qty($line) }}</td>
                        <td class="text-end">{{ $rupiah($line['price']) }}</td>
                        <td class="text-end fw-700">{{ $rupiah($line['line_total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center muted">Tidak ada data barang.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:6%;" class="text-center">No</th>
                    <th style="width:21%;">Barang</th>
                    <th style="width:13%;">Kategori</th>
                    <th style="width:12%;" class="text-end">Jumlah</th>
                    <th style="width:14%;" class="text-end">Harga Satuan</th>
                    <th style="width:15%;" class="text-end">Total Harga</th>
                    <th style="width:19%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $index => $line)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="fw-700">{{ $line['name'] }}</td>
                        <td>{{ $line['category'] }}</td>
                        <td class="text-end qty">{{ $qty($line) }}</td>
                        <td class="text-end">{{ $rupiah($line['price']) }}</td>
                        <td class="text-end fw-700">{{ $rupiah($line['line_total']) }}</td>
                        <td class="muted">{{ $line['description'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center muted">Tidak ada data barang.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- GRAND TOTAL --}}
    <table class="grand-total-table">
        <tr>
            <td class="gt-label" style="width:65%;">Total Harga Keseluruhan</td>
            <td class="gt-value" style="width:35%;">{{ $rupiah($grandTotal) }}</td>
        </tr>
    </table>
</div>

<div class="pdf-page-footer">
    <div class="note">
        Dokumen ini dihasilkan secara otomatis oleh Nextlog ADC Port Management.
    </div>
</div>
</body>
</html>
