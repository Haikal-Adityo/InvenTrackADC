<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan sederhana key-value (mis. teks PDF Form Permintaan yang bisa diedit superadmin).
 */
class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    private const CACHE_KEY = 'app_settings.all';

    /** Default teks PDF Form Permintaan (Rincian Stok Request). */
    public const PDF_STOCK_REQUEST_DEFAULTS = [
        'pdf_sr.title' => 'PERMINTAAN BARANG',
        'pdf_sr.subtitle' => 'BAGIAN UMUM',
        'pdf_sr.verify_caption' => 'Diverifikasi,',
        'pdf_sr.verify_role' => 'GA ADMIN',
        'pdf_sr.approve_caption' => 'Menyetujui,',
        'pdf_sr.approve_role' => 'LEADER',
        'pdf_sr.footer' => 'Dokumen ini dihasilkan secara otomatis oleh Nextlog ADC Port Management.',
    ];

    /** @return array<string, string|null> */
    public static function allCached(): array
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::allCached();

        // Sudah pernah disimpan → pakai apa adanya (termasuk sengaja dikosongkan)
        if (array_key_exists($key, $all)) {
            return (string) ($all[$key] ?? '');
        }

        return $default ?? (self::PDF_STOCK_REQUEST_DEFAULTS[$key] ?? null);
    }

    /** @param array<string, string|null> $values */
    public static function putMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, string> */
    public static function pdfStockRequest(): array
    {
        $out = [];
        foreach (self::PDF_STOCK_REQUEST_DEFAULTS as $key => $default) {
            $out[str_replace('pdf_sr.', '', $key)] = self::get($key, $default);
        }

        return $out;
    }
}
