<?php

namespace App\Support;

/**
 * Gambar statis untuk template PDF (DomPDF) sebagai data URI.
 * Di server cPanel public/images bisa berupa symlink ke public_html sehingga
 * DomPDF (chroot base_path) menolak path aslinya → logo tidak tampil. Data URI aman.
 */
final class PdfImage
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function dataUri(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/\\');

        if (isset(self::$cache[$relativePath])) {
            return self::$cache[$relativePath];
        }

        $full = public_path($relativePath);
        if (! is_file($full) || ! is_readable($full)) {
            return $full;
        }

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => mime_content_type($full) ?: 'image/png',
        };

        return self::$cache[$relativePath] = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($full));
    }
}
