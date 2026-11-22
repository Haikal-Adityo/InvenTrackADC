<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $settings['title'] }} {{ $stockRequest->document_number }}</title>
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
        .header-logo { width: 230px; height: auto; max-height: 70px; }
        .header-right { width: 58%; text-align: right; }
        .header-title-main { font-size: 14pt; color: #064e3b; font-weight: 700; letter-spacing: 0.5px; }
        .header-pm { font-size: 11pt; font-weight: 700; color: #3d4654; margin-top: -1px; }
        .header-number { font-size: 8.5pt; font-weight: 700; color: #059669; margin-top: 3px; }

        /* SECTION HEADING */
        .section-heading { font-size: 10.5pt; font-weight: 700; color: #064e3b; padding: 5px 0 5px 8px; border-left: 3px solid #ffd300; margin: 14px 0 8px; }

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

        /* VALIDASI (pola VMS Rekap Trans Dinas, tanpa QR) */
        .approval-text { font-size: 9pt; color: #374151; margin-bottom: 8px; line-height: 1.5; }
        .sig-wrap { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .sig-wrap td { vertical-align: top; padding: 0; }
        .sig-card { border: 1px solid #e5e7eb; border-left: 3px solid #ffd300; border-radius: 6px; padding: 10px 14px; background: #f9fafb; }
        .sig-title { color: #6b7280; font-size: 8.5pt; margin-bottom: 2px; }
        .sig-role { font-weight: 700; color: #064e3b; font-size: 9.5pt; text-transform: uppercase; margin-bottom: 2px; }
        .sig-name { font-weight: 700; color: #1f2937; font-size: 9.5pt; margin-bottom: 2px; }
        .sig-when { color: #6b7280; font-size: 8pt; }

        /* FOOTER */
        .pdf-page-footer { position: fixed; bottom: 0; left: 0; right: 0; width: 100%; padding: 6px 10px 10px; background: #fff; text-align: center; }
        .note { text-align: center; font-size: 8pt; color: #6b7280; margin-top: 6px; font-style: italic; }
    </style>
</head>
<body>
@php
    $created = $stockRequest->created_at;
    $docNo = $stockRequest->document_number;

    $rupiah = fn ($value) => 'Rp ' . number_format((int) $value, 0, ',', '.');
    $qty = fn ($line) => number_format((int) $line['quantity'], 0, ',', '.') . ($line['unit'] ? ' ' . $line['unit'] : '');

    $kategori = $stockRequest->category ?: ($lines->first()['category'] ?? '-');
    $status = $stockRequest->status;
    $tz = config('app.timezone');
    $when = fn ($dt) => $dt ? $dt->timezone($tz)->translatedFormat('d F Y') . ', ' . $dt->timezone($tz)->format('H.i') . ' WIB' : '—';
    $titleCase = fn ($v) => mb_convert_case(mb_strtolower((string) $v, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    // Request Umum yang di-approve sebelum alur 2 tahap (tanpa data verifikasi) → 1 kartu "Disetujui" oleh admin
    $legacy = $stockRequest->usesTwoStepApproval() && ! $stockRequest->verified_by;
    $twoStep = $stockRequest->usesTwoStepApproval() && ! $legacy;
    $approveCaption = $legacy ? 'Disetujui,' : $settings['approve_caption'];
    $approveRole = $legacy ? $settings['verify_role'] : $settings['approve_role'];
@endphp

<div class="page">
    {{-- HEADER --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <img class="header-logo" src="{{ \App\Support\PdfImage::dataUri('images/adcpm-landscape.png') }}" alt="Logo ADC PM">
                </td>
                <td class="header-right">
                    <div class="header-title-main">{{ mb_strtoupper($settings['title'], 'UTF-8') }}</div>
                    @if(filled($settings['subtitle']))
                        <div class="header-pm">{{ mb_strtoupper($settings['subtitle'], 'UTF-8') }}</div>
                    @endif
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
            <td class="value"><span class="status-badge status-{{ $status }}">{{ $stockRequest->status_label }}</span></td>
        </tr>
        @if($stockRequest->need_period_label)
            <tr>
                <td class="label">Periode Kebutuhan</td>
                <td class="value fw-700" colspan="3">{{ $stockRequest->need_period_label }}</td>
            </tr>
        @endif
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
                    <th style="width:14%;" class="text-end">Subtotal</th>
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
                    <th style="width:28%;">Barang</th>
                    <th style="width:12%;" class="text-end">Jumlah</th>
                    <th style="width:15%;" class="text-end">Harga Satuan</th>
                    <th style="width:16%;" class="text-end">Subtotal</th>
                    <th style="width:23%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $index => $line)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="fw-700">{{ $line['name'] }}</td>
                        <td class="text-end qty">{{ $qty($line) }}</td>
                        <td class="text-end">{{ $rupiah($line['price']) }}</td>
                        <td class="text-end fw-700">{{ $rupiah($line['line_total']) }}</td>
                        <td class="muted">{{ $line['description'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center muted">Tidak ada data barang.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    {{-- GRAND TOTAL --}}
    <table class="grand-total-table">
        <tr>
            <td class="gt-label" style="width:65%;">Grand Total</td>
            <td class="gt-value" style="width:35%;">{{ $rupiah($grandTotal) }}</td>
        </tr>
    </table>

    {{-- VALIDASI --}}
    <div style="page-break-inside: avoid;">
        <div class="section-heading">Validasi</div>
        <div class="approval-text">
            Permintaan dibuat oleh <strong>{{ $titleCase($stockRequest->user->name ?? '-') }}</strong>
            tanggal {{ $created->timezone($tz)->translatedFormat('d F Y') }} jam {{ $created->timezone($tz)->format('H.i') }} WIB
            dan telah {!! $twoStep ? '<strong>diverifikasi</strong> serta ' : '' !!}<strong>disetujui</strong> oleh:
        </div>
        <table class="sig-wrap">
            <tr>
                @if($twoStep)
                    <td style="width:48%;">
                        <div class="sig-card">
                            <div class="sig-title">{{ $settings['verify_caption'] }}</div>
                            <div class="sig-role">{{ $settings['verify_role'] }}</div>
                            <div class="sig-name">{{ mb_strtoupper($stockRequest->verifier->name ?? '—', 'UTF-8') }}</div>
                            <div class="sig-when">{{ $when($stockRequest->verified_at) }}</div>
                        </div>
                    </td>
                    <td style="width:4%;"></td>
                @endif
                <td style="width:48%;">
                    <div class="sig-card">
                        <div class="sig-title">{{ $approveCaption }}</div>
                        <div class="sig-role">{{ $approveRole }}</div>
                        <div class="sig-name">{{ mb_strtoupper($stockRequest->processor->name ?? '—', 'UTF-8') }}</div>
                        <div class="sig-when">{{ $when($stockRequest->processed_at) }}</div>
                    </div>
                </td>
                @unless($twoStep)
                    <td style="width:52%;"></td>
                @endunless
            </tr>
        </table>
    </div>
</div>

<div class="pdf-page-footer">
    <div class="note">
        {{ $settings['footer'] }}
    </div>
</div>
</body>
</html>
