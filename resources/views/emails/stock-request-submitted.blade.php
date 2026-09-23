<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    @php($appName = \App\Support\InventoryMail::appName())
    @php($pemohon = $stockRequests->first()?->user)
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #f59e0b; margin-bottom: 20px;">Request Stock Barang Baru</h2>
        <p>Halo GAADM,</p>
        <p>Ada Request Stock Barang baru di {{ $appName }} dari <strong>{{ $pemohon?->name ?? '-' }}</strong> ({{ $pemohon?->bidang ? ucfirst($pemohon->bidang) : '-' }}):</p>

        @foreach($stockRequests as $stockRequest)
            <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
                <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Kategori</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->category ?: '-' }}</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Jumlah Barang</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stockRequest->lines->count() }} jenis</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666; vertical-align: top;">Rincian</td><td style="padding: 8px; border-bottom: 1px solid #eee;">
                    @foreach($stockRequest->lines as $line)
                        {{ $line->item?->name ?? '-' }} &times; {{ $line->quantity }}<br>
                    @endforeach
                </td></tr>
            </table>
        @endforeach

        <p>Silakan tinjau dan proses request ini di menu Stok Request.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem {{ $appName }}.</p>
    </div>
</body>
</html>
