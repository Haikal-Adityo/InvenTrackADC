<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu PDF Rekap Persediaan Barang yang pernah dibuat.
 * id = nomor urut global "Cetakan ke-N"; file disimpan supaya membuka ulang tidak membuat cetakan baru.
 */
class DocumentPrint extends Model
{
    protected $table = 'recap_prints';

    protected $fillable = ['document_number', 'bidang', 'filters', 'row_count', 'grand_total', 'file_path', 'printed_by'];

    protected function casts(): array
    {
        return ['filters' => 'array'];
    }

    public function printer()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /** ADC-{yy}{bulan romawi}REK{N 3 digit} — N = cetakan ke-N (global). */
    public static function numberFor(int $id, ?\DateTimeInterface $at = null): string
    {
        $at ??= now();
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

        return 'ADC-'.$at->format('y').$romawi[(int) $at->format('n')].'REK'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }
}
