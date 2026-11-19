<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    @php($appName = \App\Support\InventoryMail::appName())
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #10b981; margin-bottom: 20px;">Transaksi Baru Menunggu Approval</h2>
        <p>Halo Admin,</p>
        <p>Transaksi baru di {{ $appName }} telah ditambahkan dan menunggu approval:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Tanggal</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $transaction->date->format('d/m/Y') }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Barang</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $transaction->item->name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Jenis</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ strtoupper($transaction->type) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Jumlah</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $transaction->quantity }} {{ $transaction->item->unit }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Diinput oleh</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $transaction->user?->name ?? 'Input publik' }}</td></tr>
        </table>
        <p>Silakan login ke <a href="{{ url('/dashboard') }}" style="color: #10b981;">{{ $appName }}</a> untuk melakukan approval.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem {{ $appName }}.</p>
    </div>
</body>
</html>
