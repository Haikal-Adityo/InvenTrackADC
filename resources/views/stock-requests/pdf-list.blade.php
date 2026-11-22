<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Persediaan Barang {{ $docNumber }}</title>
    <style>
        @page { margin: 30px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; color: #1a1a2e; line-height: 1.35; margin: 10px; padding: 10px; position: relative; }
        .page { padding: 10px; padding-bottom: 84px; }

        /* HEADER (kop surat pola VMS) */
        .header { width: 100%; margin-bottom: 12px; border-bottom: 3px solid #10b981; padding-bottom: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-left { width: 40%; }
        .header-logo { width: 220px; height: auto; max-height: 66px; }
        .header-right { width: 60%; text-align: right; }
        .header-title-main { font-size: 14pt; color: #064e3b; font-weight: 700; letter-spacing: 0.5px; }
        .header-pm { font-size: 10.5pt; font-weight: 700; color: #3d4654; }
        .header-number { font-size: 8pt; font-weight: 700; color: #059669; margin-top: 3px; }

        .section-heading { font-size: 10pt; font-weight: 700; color: #064e3b; padding: 4px 0 4px 8px; border-left: 3px solid #ffd300; margin: 12px 0 6px; }

        .status-approved { color: #059669; font-weight: 700; }
        .status-pending, .status-verified { color: #d97706; font-weight: 700; }
        .status-rejected { color: #dc2626; font-weight: 700; }

        /* DATA TABLE */
        .data-table { width: 100%; border-collapse: collapse; font-size: 8pt; table-layout: fixed; }
        .data-table thead { display: table-header-group; }
        .data-table th { background: #f1f5f9; padding: 5px; text-align: left; font-weight: 700; color: #374151; border: 1px solid #d1d5db; font-size: 7pt; text-transform: uppercase; }
        .data-table td { padding: 4px 5px; border: 1px solid #d1d5db; vertical-align: middle; word-wrap: break-word; }
        .data-table tr { page-break-inside: avoid; }
        .data-table .subtotal-row td { background: #f8fafc; font-weight: 700; }
        .text-end { text-align: right !important; }
        .text-center { text-align: center !important; }
        .fw-700 { font-weight: 700; }
        .qty { font-weight: 700; color: #059669; }
        .muted { color: #6c757d; }

        .grand-total-table { width: 100%; border-collapse: collapse; margin-top: 14px; page-break-inside: avoid; }
        .grand-total-table td { padding: 8px 12px; border: 1px solid #a7f3d0; background: #ecfdf5; }
        .grand-total-table .gt-label { font-weight: 700; color: #374151; font-size: 9.5pt; border-right: none; }
        .grand-total-table .gt-value { font-weight: 700; color: #059669; text-align: right; font-size: 12pt; border-left: none; }

        /* FOOTER: QR log aktivitas | pencetak / tgl jam / cetakan ke-N */
        .pdf-page-footer { position: fixed; bottom: 0; left: 10px; right: 10px; height: 66px; border-top: 1px solid #e5e7eb; padding-top: 4px; background: #fff; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { vertical-align: middle; font-size: 7.5pt; color: #4b5563; }
        .footer-qr { width: 58px; height: 58px; }
        .footer-sep { color: #9ca3af; padding: 0 6px; }
        .footer-note { text-align: right; font-style: italic; color: #9ca3af; }
    </style>
</head>
<body>
@php
    $rupiah = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $qty = fn ($row) => number_format((int) $row['quantity'], 0, ',', '.') . ($row['unit'] ? ' ' . $row['unit'] : '');
@endphp

<div class="pdf-page-footer">
    <table class="footer-table">
        <tr>
            <td style="width:66px;">
                @if($qr)
                    <img class="footer-qr" src="{{ $qr }}" alt="QR Log Aktivitas">
                @endif
            </td>
            <td>
                <strong>QR Log Aktivitas</strong><span class="footer-sep">|</span>
                {{ mb_strtoupper($printedBy, 'UTF-8') }} / {{ $docDate->translatedFormat('d F Y H.i') }} WIB / Cetakan ke-{{ $printCount }}
            </td>
            <td class="footer-note">Dokumen ini dihasilkan secara otomatis oleh Nextlog ADC Port Management.</td>
        </tr>
    </table>
</div>

<div class="page">
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img class="header-logo" src="{{ \App\Support\PdfImage::dataUri('images/adcpm-landscape.png') }}" alt="Logo ADC PM">
                </td>
                <td class="header-right">
                    <div class="header-title-main">REKAP PERSEDIAAN BARANG</div>
                    <div class="header-pm">{{ $bidang }}</div>
                    <div class="header-number">No. {{ $docNumber }} | {{ $docDate->translatedFormat('d F Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @forelse($groups as $group)
        <div class="section-heading">{{ $group['category'] }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:4%;" class="text-center">No</th>
                    <th style="width:12%;">No. Request</th>
                    <th style="width:8%;">Tanggal</th>
                    <th style="width:10%;">Pemohon</th>
                    @if($isTeknik)
                        <th style="width:11%;">No Normalisasi</th>
                    @endif
                    <th style="width:{{ $isTeknik ? 15 : 22 }}%;">Barang</th>
                    <th style="width:8%;" class="text-end">Jumlah</th>
                    <th style="width:10%;" class="text-end">Harga Satuan</th>
                    <th style="width:10%;" class="text-end">Subtotal</th>
                    <th style="width:9%;">Status</th>
                    <th style="width:7%;">Diproses Oleh</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['rows'] as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $row['request_number'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['requester'] }}</td>
                        @if($isTeknik)
                            <td>{{ $row['no_normalisasi'] }}</td>
                        @endif
                        <td class="fw-700">{{ $row['name'] }}</td>
                        <td class="text-end qty">{{ $qty($row) }}</td>
                        <td class="text-end">{{ $rupiah($row['price']) }}</td>
                        <td class="text-end fw-700">{{ $rupiah($row['line_total']) }}</td>
                        <td class="status-{{ $row['status'] }}">{{ $row['status_label'] }}</td>
                        <td>{{ $row['processor'] }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="{{ $isTeknik ? 8 : 7 }}" class="text-end">Total {{ $group['category'] }}</td>
                    <td class="text-end">{{ $rupiah($group['subtotal']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    @empty
        <p class="muted" style="text-align:center; padding:20px;">Tidak ada data stok request untuk filter ini.</p>
    @endforelse

    <table class="grand-total-table">
        <tr>
            <td class="gt-label" style="width:65%;">Grand Total</td>
            <td class="gt-value" style="width:35%;">{{ $rupiah($grandTotal) }}</td>
        </tr>
    </table>
</div>
</body>
</html>
