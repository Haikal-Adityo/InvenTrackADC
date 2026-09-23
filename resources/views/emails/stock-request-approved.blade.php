<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    @php($appName = \App\Support\InventoryMail::appName())
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #10b981; margin-bottom: 20px;">Stock Request Disetujui</h2>
        <p>Halo GAADM,</p>
        <p>Stock Request #{{ $stockRequest->id }} di {{ $appName }} telah <strong>disetujui</strong>:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Pemohon</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->user?->name ?? '-' }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Kategori</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->category ?: '-' }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Jumlah Barang</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->lines->count() }} jenis</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Disetujui Oleh</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->processor?->name ?? '-' }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Waktu</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->processed_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
        </table>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem {{ $appName }}.</p>
    </div>
</body>
</html>
