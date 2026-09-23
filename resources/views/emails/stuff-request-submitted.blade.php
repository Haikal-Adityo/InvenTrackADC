<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px;">
    @php($appName = \App\Support\InventoryMail::appName())
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="color: #f59e0b; margin-bottom: 20px;">Permintaan Barang Baru</h2>
        <p>Halo GAADM,</p>
        <p>Ada Permintaan Barang baru di {{ $appName }}:</p>
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Nama Pemohon</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stuffRequest->requester_name }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">NIP</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stuffRequest->nip }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Jabatan</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ $stuffRequest->jabatan }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Bidang</td><td style="padding: 8px; border-bottom: 1px solid #eee; font-weight: bold;">{{ ucfirst($stuffRequest->bidang) }}</td></tr>
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666; vertical-align: top;">Barang Diminta</td><td style="padding: 8px; border-bottom: 1px solid #eee;">
                @foreach($stuffRequest->lines as $line)
                    {{ $line->item?->name ?? '-' }} &times; {{ $line->quantity }}<br>
                @endforeach
            </td></tr>
            @if($stuffRequest->notes)
            <tr><td style="padding: 8px; border-bottom: 1px solid #eee; color: #666;">Kebutuhan</td><td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $stuffRequest->notes }}</td></tr>
            @endif
        </table>
        <p>Silakan tinjau dan proses permintaan ini di menu Permintaan Barang.</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="color: #999; font-size: 12px;">Email ini dikirim otomatis oleh sistem {{ $appName }}.</p>
    </div>
</body>
</html>
