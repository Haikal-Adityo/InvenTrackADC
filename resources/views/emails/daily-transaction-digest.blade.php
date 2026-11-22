<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
    @php($appName = \App\Support\InventoryMail::appName())
    <div style="max-width: 680px; margin: 0 auto; background: white; border-radius: 12px; padding: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #10b981, #059669); padding: 28px 30px; color: white;">
            <h2 style="margin: 0 0 4px 0; font-size: 20px;">📦 Ringkasan Transaksi Pending</h2>
            <p style="margin: 0; opacity: 0.85; font-size: 14px;">{{ $dateLabel }}</p>
        </div>

        <div style="padding: 28px 30px;">
            <p style="color: #333; font-size: 14px; margin: 0 0 6px;">Halo Admin,</p>
            <p style="color: #333; font-size: 14px; margin: 0 0 20px;">Berikut adalah <strong>{{ $transactions->count() }} transaksi bidang umum</strong> di {{ $appName }} yang menunggu approval hari ini:</p>

            {{-- Table --}}
            <table style="width: 100%; border-collapse: collapse; margin: 0 0 20px; font-size: 13px;">
                <thead>
                    <tr style="background: #f0fdf4;">
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #10b981; color: #059669; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">No</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #10b981; color: #059669; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Tanggal</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #10b981; color: #059669; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Barang</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #10b981; color: #059669; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Jenis</th>
                        <th style="padding: 10px 12px; text-align: right; border-bottom: 2px solid #10b981; color: #059669; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Jumlah</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #10b981; color: #059669; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">User</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $i => $tx)
                    <tr style="background: {{ $i % 2 == 0 ? '#ffffff' : '#f9fafb' }};">
                        <td style="padding: 10px 12px; border-bottom: 1px solid #eee; color: #666;">{{ $loop->iteration }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #eee; color: #333;">{{ $tx->date->format('d/m/Y') }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #eee; color: #333; font-weight: 600;">{{ $tx->item->name ?? '-' }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #eee;">
                            @if($tx->type === 'in')
                                <span style="background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">IN</span>
                            @else
                                <span style="background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">OUT</span>
                            @endif
                        </td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #eee; color: #333; font-weight: 700; text-align: right;">{{ $tx->quantity }} {{ $tx->item->unit ?? '' }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #eee; color: #666;">{{ $tx->user->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="color: #333; font-size: 14px; margin: 0 0 20px;">
                Silakan login ke <a href="{{ url('/dashboard') }}" style="color: #10b981; font-weight: 600;">{{ $appName }}</a> untuk melakukan review dan approval.
            </p>

            <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="color: #999; font-size: 12px; margin: 0;">Email ini dikirim otomatis oleh sistem {{ $appName }} setiap hari pukul 15:00 WIB.</p>
        </div>
    </div>
</body>
</html>
