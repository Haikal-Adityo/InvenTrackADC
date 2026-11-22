<?php

namespace App\Imports;

use Carbon\Carbon;

/**
 * Parser tanggal import Excel yang ketat.
 * - Sel tanggal Excel (angka serial) dibaca apa adanya.
 * - Teks "a/b/yyyy": kalau a > 12 → d/m, kalau b > 12 → m/d, kalau ambigu → ikut urutan
 *   yang terdeteksi di file (lihat detectOrder), default d/m (sesuai template 17/04/2026).
 * - Tanggal tidak valid (mis. bulan 17) TIDAK lagi "meluber" ke tahun berikutnya.
 */
final class ImportDate
{
    public static function parse($value, string $order = 'dmy'): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if (is_numeric($value)) {
            $n = (float) $value;
            if ($n < 20000 || $n > 80000) {      // di luar ±1954–2119: bukan tanggal Excel
                return null;
            }

            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($n))->startOfDay();
        }

        $s = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $s, $m)) {
            return self::make((int) $m[1], (int) $m[2], (int) $m[3]);
        }

        if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2}|\d{4})$#', $s, $m)) {
            [$a, $b, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if ($y < 100) {
                $y += 2000;
            }
            if ($a > 12 && $b <= 12) {
                return self::make($y, $b, $a);         // d/m
            }
            if ($b > 12 && $a <= 12) {
                return self::make($y, $a, $b);         // m/d
            }

            return $order === 'mdy' ? self::make($y, $a, $b) : self::make($y, $b, $a);
        }

        return null;
    }

    /**
     * Tebak urutan tanggal teks di file: 'mdy' kalau ada "a/b" dengan b > 12, 'dmy' kalau a > 12.
     *
     * @param  iterable<mixed>  $values
     */
    public static function detectOrder(iterable $values): string
    {
        $dmy = 0;
        $mdy = 0;
        foreach ($values as $v) {
            if (! is_string($v) || ! preg_match('#^\s*(\d{1,2})[/\-.](\d{1,2})[/\-.]\d{2,4}\s*$#', $v, $m)) {
                continue;
            }
            if ((int) $m[1] > 12) $dmy++;
            if ((int) $m[2] > 12) $mdy++;
        }

        return $mdy > $dmy ? 'mdy' : 'dmy';
    }

    private static function make(int $y, int $m, int $d): ?Carbon
    {
        return checkdate($m, $d, $y) ? Carbon::create($y, $m, $d)->startOfDay() : null;
    }
}
