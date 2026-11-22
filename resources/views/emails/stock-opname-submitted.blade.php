<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    @php($appName = \App\Support\InventoryMail::appName())
    @php($so = $stockOpname)
    @php($diff = $so->lines->where('difference', '!=', 0))
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #f59e0b; margin-bottom: 20px;">Stok Opname Butuh Verifikasi</h2>
        <p>Halo GA Admin,</p>
        <p><strong>{{ $so->user?->name ?? '-' }}</strong> telah melakukan stok opname dan menunggu verifikasi Anda:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">No. SO</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $so->document_number }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Tanggal & Jam</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $so->counted_at->timezone(config('app.timezone'))->translatedFormat('d F Y H:i') }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Jumlah Item</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $so->lines->count() }} barang ({{ $diff->count() }} selisih)</td></tr>
            @if($diff->isNotEmpty())
                <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666; vertical-align: top;">Selisih</td><td style="padding: 8px; border-bottom: 1px solid #eee;">
                    @foreach($diff as $line)
                        {{ $line->item?->name ?? '-' }}: {{ $line->difference > 0 ? '+' : '' }}{{ $line->difference }}<br>
                    @endforeach
                </td></tr>
            @endif
        </table>
        <p>Silakan verifikasi di menu Rekap Stok → tab Stok Opname. Selisih akan otomatis disesuaikan setelah diverifikasi.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem {{ $appName }}.</p>
    </div>
</body>
</html>
