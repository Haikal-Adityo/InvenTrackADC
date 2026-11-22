<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    @php($appName = \App\Support\InventoryMail::appName())
    @php($sr = $stockRequest)
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #6366f1; margin-bottom: 20px;">Stok Request Menunggu Persetujuan</h2>
        <p>Halo Leader,</p>
        <p>Stok Request berikut sudah <strong>diverifikasi</strong> oleh {{ $sr->verifier?->name ?? 'GA Admin' }} dan menunggu persetujuan Anda:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">No. Request</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $sr->document_number }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Pemohon</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $sr->user?->name ?? '-' }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Kategori</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $sr->category ?: '-' }}</td></tr>
            @if($sr->need_period_label)
                <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Periode Kebutuhan</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $sr->need_period_label }}</td></tr>
            @endif
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666; vertical-align: top;">Rincian</td><td style="padding: 8px; border-bottom: 1px solid #eee;">
                @foreach($sr->lines as $line)
                    {{ $line->item?->name ?? '-' }} &times; {{ $line->quantity }}<br>
                @endforeach
            </td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Grand Total</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">Rp {{ number_format($sr->lines->sum(fn ($l) => (int) $l->price * (int) $l->quantity), 0, ',', '.') }}</td></tr>
        </table>

        <p>Silakan setujui atau tolak request ini di menu Stok Request.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem {{ $appName }}.</p>
    </div>
</body>
</html>
