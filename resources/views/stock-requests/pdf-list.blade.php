<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Stok Request</title>
    <style>
        @page { margin: 30px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; color: #1a1a2e; line-height: 1.35; margin: 10px; padding: 10px; position: relative; }

        /* Sama dengan PDF rincian per request: padding di .page supaya isi tidak menempel ke tepi kertas */
        .page { padding: 10px; padding-bottom: 40px; }

        /* HEADER (sama dengan PDF rincian per request) */
        .header { width: 100%; margin-bottom: 12px; border-bottom: 3px solid #10b981; padding-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-left { width: 40%; }
        .header-logo { width: 230px; height: auto; max-height: 56px; }
        .header-right { width: 60%; text-align: right; }
        .header-title-main { font-size: 14pt; color: #064e3b; font-weight: 700; letter-spacing: 0.5px; }
        .header-pm { font-size: 10.5pt; font-weight: 700; color: #3d4654; }
        .header-number { font-size: 8pt; font-weight: 700; color: #059669; margin-top: 3px; }

        .section-heading { font-size: 10pt; font-weight: 700; color: #064e3b; padding: 4px 0 4px 8px; border-left: 3px solid #10b981; margin: 10px 0 6px; }

        /* INFO FILTER + RINGKASAN */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: fixed; }
        .info-table td { border: 1px solid #d1d5db; padding: 5px 8px; font-size: 8.5pt; }
        .info-table .label { font-weight: 700; background: #ecfdf5; width: 13%; }
        .info-table .value { width: 20.3%; }

        .status-approved { color: #059669; font-weight: 700; }
        .status-pending { color: #d97706; font-weight: 700; }
        .status-rejected { color: #dc2626; font-weight: 700; }

        /* DATA TABLE */
        .data-table { width: 100%; border-collapse: collapse; font-size: 8pt; table-layout: fixed; }
        .data-table thead { display: table-header-group; }
        .data-table th { background: #f1f5f9; padding: 5px; text-align: left; font-weight: 700; color: #374151; border: 1px solid #d1d5db; font-size: 7pt; text-transform: uppercase; }
        .data-table td { padding: 4px 5px; border: 1px solid #d1d5db; vertical-align: middle; word-wrap: break-word; }
        .data-table tr { page-break-inside: avoid; }
        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .fw-700 { font-weight: 700; }
        .qty { font-weight: 700; color: #059669; }
        .muted { color: #6c757d; }

        .grand-total-table { width: 100%; border-collapse: collapse; margin-top: 8px; page-break-inside: avoid; }
        .grand-total-table td { padding: 8px 12px; border: 1px solid #a7f3d0; background: #ecfdf5; }
        .grand-total-table .gt-label { font-weight: 700; color: #374151; font-size: 9pt; border-right: none; }
        .grand-total-table .gt-value { font-weight: 700; color: #059669; text-align: right; font-size: 11.5pt; border-left: none; }

        .pdf-page-footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 6px 10px 10px; text-align: center; font-size: 7.5pt; color: #6b7280; font-style: italic; }
    </style>
</head>
<body>
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $qty = fn ($row) => number_format((int) $row['quantity'], 0, ',', '.') . ($row['unit'] ? ' ' . $row['unit'] : '');
    $colspan = $isTeknik ? 11 : 10;
@endphp

<div class="page">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img class="header-logo" src="{{ public_path('images/logo-perusahaan.png') }}" alt="Logo Perusahaan">
                </td>
                <td class="header-right">
                    <div class="header-title-main">REKAP STOK REQUEST</div>
                    <div class="header-pm">{{ $bidang }}</div>
                    <div class="header-number">Dicetak {{ now()->translatedFormat('d F Y H:i') }} oleh {{ $printedBy }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-heading">Filter &amp; Ringkasan</div>
    <table class="info-table">
        <tr>
            <td class="label">Status</td>
            <td class="value fw-700">{{ $filters['status'] }}</td>
            <td class="label">Periode</td>
            <td class="value">{{ $filters['periode'] }}</td>
            <td class="label">Kategori</td>
            <td class="value">{{ $filters['category'] }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Request</td>
            <td class="value fw-700">{{ $totalRequests }} request ({{ $rows->count() }} baris barang)</td>
            <td class="label">Rincian Status</td>
            <td class="value" colspan="3">
                <span class="status-approved">Approved: {{ $statusCounts['approved'] }}</span> &nbsp;|&nbsp;
                <span class="status-pending">Pending: {{ $statusCounts['pending'] }}</span> &nbsp;|&nbsp;
                <span class="status-rejected">Rejected: {{ $statusCounts['rejected'] }}</span>
            </td>
        </tr>
    </table>

    <div class="section-heading">Daftar Barang</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:4%;" class="text-center">No</th>
                <th style="width:7%;">Tanggal</th>
                <th style="width:{{ $isTeknik ? 10 : 12 }}%;">Pemohon</th>
                @if($isTeknik)
                    <th style="width:10%;">No Normalisasi</th>
                @endif
                <th style="width:{{ $isTeknik ? 15 : 20 }}%;">Barang</th>
                <th style="width:{{ $isTeknik ? 9 : 11 }}%;">{{ $isTeknik ? 'Komponen' : 'Kategori' }}</th>
                <th style="width:8%;" class="text-end">Jumlah</th>
                <th style="width:10%;" class="text-end">Harga Satuan</th>
                <th style="width:11%;" class="text-end">Total Harga</th>
                <th style="width:7%;" class="text-center">Status</th>
                <th style="width:{{ $isTeknik ? 9 : 10 }}%;">Diproses Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['requester'] }}</td>
                    @if($isTeknik)
                        <td class="fw-700">{{ $row['no_normalisasi'] }}</td>
                    @endif
                    <td class="fw-700">{{ $row['name'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td class="text-end qty">{{ $qty($row) }}</td>
                    <td class="text-end">{{ $rupiah($row['price']) }}</td>
                    <td class="text-end fw-700">{{ $rupiah($row['line_total']) }}</td>
                    <td class="text-center status-{{ $row['status'] }}">{{ ucfirst($row['status']) }}</td>
                    <td>{{ $row['processor'] }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $colspan }}" class="text-center muted">Tidak ada data stok request untuk filter ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="grand-total-table">
        <tr>
            <td class="gt-label" style="width:65%;">Total Harga Keseluruhan</td>
            <td class="gt-value" style="width:35%;">{{ $rupiah($grandTotal) }}</td>
        </tr>
    </table>
</div>

<div class="pdf-page-footer">
    Dokumen ini dihasilkan secara otomatis oleh Nextlog ADC Port Management.
</div>

</body>
</html>
