<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; margin: 0;">
    @php($appName = \App\Support\InventoryMail::appName())
    <div style="max-width: 680px; margin: 0 auto; background: white; border-radius: 12px; padding: 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #ef4444, #dc2626); padding: 28px 30px; color: white;">
            <h2 style="margin: 0 0 4px 0; font-size: 20px;">⚠️ Peringatan Stok Harian</h2>
            <p style="margin: 0; opacity: 0.85; font-size: 14px;">{{ $dateLabel }}</p>
        </div>

        <div style="padding: 28px 30px;">
            <p style="color: #333; font-size: 14px; margin: 0 0 6px;">Halo Admin,</p>
            <p style="color: #333; font-size: 14px; margin: 0 0 20px;">
                Terdapat <strong>{{ $outOfStockItems->count() + $lowStockItems->count() }} barang bidang umum</strong> di {{ $appName }} yang memerlukan perhatian:
            </p>

            {{-- Summary badges --}}
            <div style="margin-bottom: 24px;">
                @if($outOfStockItems->count() > 0)
                <span style="display: inline-block; background: #fef2f2; color: #dc2626; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-right: 8px; border: 1px solid #fecaca;">
                    🔴 {{ $outOfStockItems->count() }} Stok Habis
                </span>
                @endif
                @if($lowStockItems->count() > 0)
                <span style="display: inline-block; background: #fffbeb; color: #d97706; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid #fde68a;">
                    🟡 {{ $lowStockItems->count() }} Request Order
                </span>
                @endif
            </div>

            {{-- Stok Habis (Merah) --}}
            @if($outOfStockItems->count() > 0)
            <h3 style="color: #dc2626; font-size: 15px; margin: 0 0 10px; padding-bottom: 8px; border-bottom: 2px solid #fecaca;">🔴 Peringatan Stok Habis</h3>
            <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px; font-size: 13px;">
                <thead>
                    <tr style="background: #fef2f2;">
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #ef4444; color: #dc2626; font-size: 11px; text-transform: uppercase;">No</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #ef4444; color: #dc2626; font-size: 11px; text-transform: uppercase;">Barang</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #ef4444; color: #dc2626; font-size: 11px; text-transform: uppercase;">Kategori</th>
                        <th style="padding: 10px 12px; text-align: center; border-bottom: 2px solid #ef4444; color: #dc2626; font-size: 11px; text-transform: uppercase;">Stok</th>
                        <th style="padding: 10px 12px; text-align: center; border-bottom: 2px solid #ef4444; color: #dc2626; font-size: 11px; text-transform: uppercase;">Min Stok</th>
                        <th style="padding: 10px 12px; text-align: center; border-bottom: 2px solid #ef4444; color: #dc2626; font-size: 11px; text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($outOfStockItems as $i => $item)
                    <tr style="background: {{ $loop->odd ? '#fff5f5' : '#ffffff' }};">
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fecaca; color: #666;">{{ $loop->iteration }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fecaca; color: #333; font-weight: 600;">{{ $item->name }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fecaca; color: #666;">{{ $item->category }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fecaca; text-align: center; font-weight: 700; color: #dc2626;">{{ $item->current_stock }} {{ $item->unit }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fecaca; text-align: center; color: #666;">{{ $item->min_stock }} {{ $item->unit }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fecaca; text-align: center;">
                            <span style="background: #dc2626; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">Stok Habis</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            {{-- Request Order (Kuning) --}}
            @if($lowStockItems->count() > 0)
            <h3 style="color: #d97706; font-size: 15px; margin: 0 0 10px; padding-bottom: 8px; border-bottom: 2px solid #fde68a;">🟡 Peringatan Request Order</h3>
            <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px; font-size: 13px;">
                <thead>
                    <tr style="background: #fffbeb;">
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #f59e0b; color: #d97706; font-size: 11px; text-transform: uppercase;">No</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #f59e0b; color: #d97706; font-size: 11px; text-transform: uppercase;">Barang</th>
                        <th style="padding: 10px 12px; text-align: left; border-bottom: 2px solid #f59e0b; color: #d97706; font-size: 11px; text-transform: uppercase;">Kategori</th>
                        <th style="padding: 10px 12px; text-align: center; border-bottom: 2px solid #f59e0b; color: #d97706; font-size: 11px; text-transform: uppercase;">Stok</th>
                        <th style="padding: 10px 12px; text-align: center; border-bottom: 2px solid #f59e0b; color: #d97706; font-size: 11px; text-transform: uppercase;">Min Stok</th>
                        <th style="padding: 10px 12px; text-align: center; border-bottom: 2px solid #f59e0b; color: #d97706; font-size: 11px; text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockItems as $i => $item)
                    <tr style="background: {{ $loop->odd ? '#fffef5' : '#ffffff' }};">
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fde68a; color: #666;">{{ $loop->iteration }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fde68a; color: #333; font-weight: 600;">{{ $item->name }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fde68a; color: #666;">{{ $item->category }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fde68a; text-align: center; font-weight: 700; color: #d97706;">{{ $item->current_stock }} {{ $item->unit }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fde68a; text-align: center; color: #666;">{{ $item->min_stock }} {{ $item->unit }}</td>
                        <td style="padding: 10px 12px; border-bottom: 1px solid #fde68a; text-align: center;">
                            <span style="background: #f59e0b; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">Request Order</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            <p style="color: #333; font-size: 14px; margin: 0 0 20px;">
                Silakan segera lakukan tindakan untuk barang-barang di atas. Login ke <a href="{{ url('/stock') }}" style="color: #10b981; font-weight: 600;">{{ $appName }}</a> untuk detail.
            </p>

            <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="color: #999; font-size: 12px; margin: 0;">Email ini dikirim otomatis oleh sistem {{ $appName }} setiap hari pukul 15:00 WIB.</p>
        </div>
    </div>
</body>
</html>
