<?php

namespace App\Support;

/**
 * QR code (PNG data URI) untuk PDF. Memakai endroid/qr-code bila terpasang;
 * kalau belum, kembalikan null supaya PDF tetap bisa dicetak tanpa QR.
 */
final class PdfQr
{
    public static function dataUri(string $text, int $size = 220): ?string
    {
        $text = trim($text) === '' ? '-' : trim($text);

        try {
            if (class_exists(\Endroid\QrCode\QrCode::class) && class_exists(\Endroid\QrCode\Writer\PngWriter::class)) {
                // endroid/qr-code v6 (konstruktor named args)
                $qr = new \Endroid\QrCode\QrCode(data: $text, size: $size, margin: 4);

                return (new \Endroid\QrCode\Writer\PngWriter())->write($qr)->getDataUri();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal membuat QR PDF', ['message' => $e->getMessage()]);
        }

        return null;
    }
}
