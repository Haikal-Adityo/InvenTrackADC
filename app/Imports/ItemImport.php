<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithColumnLimit;

class ItemImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows, WithColumnLimit, WithCalculatedFormulas
{
    use SkipsFailures, Importable;

    private int $rowCount = 0;
    private int $skipCount = 0;

    public function __construct(private ?User $user = null)
    {
    }

    /** Hanya baca kolom A..Z: file Excel klien tercatat sampai kolom XFD (16.384 kolom) → 500 kehabisan memori. */
    public function endColumn(): string
    {
        return 'Z';
    }

    public function prepareForValidation($data, $index)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(preg_replace('/\s+/u', ' ', $value));
            }
        }

        return $data;
    }

    public function model(array $row)
    {
        // Skip if item with same name already exists
        $bidang = $this->user?->isSuperAdmin()
            ? strtolower(trim($row['bidang'] ?? 'umum'))
            : $this->user?->bidang;
        $bidang = in_array($bidang, ['teknik', 'umum'], true) ? $bidang : 'umum';

        $name = $this->cellString($row['nama_barang'] ?? '');
        $noNormalisasi = $this->cellString($row['no_normalisasi'] ?? '');

        $existing = Item::where('bidang', $bidang)
            ->where(function ($query) use ($name, $noNormalisasi) {
                $query->where('name', $name);

                if ($noNormalisasi !== '') {
                    $query->orWhere('no_normalisasi', $noNormalisasi);
                }
            })
            ->first();
        if ($existing) {
            $this->skipCount++;
            return null;
        }

        $this->rowCount++;

        return new Item([
            'name'      => $name,
            'no_normalisasi' => $bidang === 'teknik' ? $noNormalisasi : null,
            'category'  => $this->cellString($row['kategori'] ?? ''),
            'component' => $bidang === 'teknik' ? $this->cellString($row['komponen'] ?? '') : null,
            'unit'      => $this->cellString($row['satuan'] ?? ''),
            'bidang'    => $bidang,
            'lokasi'    => $bidang === 'teknik' ? $this->cellString($row['lokasi'] ?? '') : null,
            'volume'    => $bidang === 'teknik' && $this->cellString($row['volume'] ?? '') !== '' ? (int) $row['volume'] : null,
            'ship_unloader' => null,
            'min_stock' => (int) ($row['min_stok'] ?? 0),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required|max:255',
            'kategori'    => 'required|max:255',
            'komponen'    => 'nullable|max:255',
            'satuan'      => 'required|max:50',
            'bidang'      => 'nullable|in:teknik,umum',
            'no_normalisasi' => 'nullable|max:255',
            'lokasi'      => 'nullable|max:255',
            'volume'      => 'nullable|integer|min:0',
            'min_stok'    => 'nullable|integer|min:0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_barang.required' => 'Kolom nama_barang wajib diisi.',
            'kategori.required'    => 'Kolom kategori wajib diisi.',
            'satuan.required'      => 'Kolom satuan wajib diisi.',
            'min_stok.integer'     => 'Kolom min_stok harus angka.',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    private function normalizeShipUnloader($value): ?string
    {
        $value = str_replace(['.', ';', '-', ' '], ',', $this->cellString($value));

        $ships = collect(explode(',', $value))
            ->map(fn($ship) => trim($ship))
            ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();

        return $ships->isEmpty() ? null : $ships->implode(',');
    }

    private function cellString($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }
}
