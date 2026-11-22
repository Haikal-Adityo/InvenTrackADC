<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Transaction;
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
use Carbon\Carbon;

class TransactionImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows, WithColumnLimit, WithCalculatedFormulas
{
    use SkipsFailures, Importable;

    private int $rowCount = 0;
    private int $duplicateCount = 0;
    private array $unmatchedItems = [];

    /**
     * @param  string  $dateOrder  urutan tanggal teks di file ('dmy' / 'mdy'), dideteksi ImportController
     * @param  string|null  $batch  penanda batch import (untuk batalkan import & cegah import ganda)
     */
    public function __construct(private ?User $user = null, private string $dateOrder = 'dmy', private ?string $batch = null)
    {
    }

    /** Hanya baca kolom A..Z (file dari Excel kadang tercatat sampai kolom XFD → 500/kehabisan memori). */
    public function endColumn(): string
    {
        return 'Z';
    }

    /**
     * Rapikan isi sel sebelum divalidasi: spasi di ujung, huruf besar/kecil,
     * "Masuk"/"Keluar" → in/out, tanggal teks → Y-m-d (tanggal tidak valid dikosongkan supaya ditolak).
     */
    public function prepareForValidation($data, $index)
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim(preg_replace('/\s+/u', ' ', $value));
            }
        }

        if (isset($data['jenis']) && is_string($data['jenis'])) {
            $jenis = strtolower($data['jenis']);
            $data['jenis'] = match ($jenis) {
                'in', 'masuk', 'barang masuk', 'gr', 'goods receipt' => 'in',
                'out', 'keluar', 'barang keluar', 'gi', 'goods issue' => 'out',
                default => $jenis,
            };
        }

        if (array_key_exists('tanggal', $data)) {
            $date = ImportDate::parse($data['tanggal'], $this->dateOrder);
            $data['tanggal'] = $date?->format('Y-m-d') ?? (filled($data['tanggal']) ? 'invalid:'.$data['tanggal'] : null);
        }

        return $data;
    }

    public function model(array $row)
    {
        $itemName = $this->cellString($row['nama_barang'] ?? '');
        $noNormalisasi = $this->cellString($row['no_normalisasi'] ?? '');
        $item = $this->resolveItem($itemName, $noNormalisasi);

        if (!$item) {
            $this->unmatchedItems[] = $noNormalisasi ?: $itemName;
            return null;
        }

        $date = $row['tanggal'];   // sudah dinormalisasi & divalidasi (Y-m-d)
        $isTeknik = $item->bidang === 'teknik';
        $quantity = (int) ($row['jumlah'] ?? $row['volume']);
        $price = $this->cellString($row['harga'] ?? '') === ''
            ? null
            : (int) $row['harga'];
        $shipUnloader = $isTeknik
            ? ($this->normalizeShipUnloader($row['ship_unloader'] ?? '') ?: $item->ship_unloader)
            : null;

        // Baris yang sama persis sudah pernah masuk dari import SEBELUMNYA → lewati (cegah import file 2x)
        $duplicate = Transaction::query()
            ->whereNotNull('import_batch')
            ->when($this->batch, fn ($q) => $q->where('import_batch', '!=', $this->batch))
            ->where('item_id', $item->id)
            ->whereDate('date', $date)
            ->where('type', $row['jenis'])
            ->where('quantity', $quantity)
            ->where(fn ($q) => $price === null ? $q->whereNull('price') : $q->where('price', $price))
            ->where('status', '!=', 'rejected')
            ->exists();
        if ($duplicate) {
            $this->duplicateCount++;
            return null;
        }

        $this->rowCount++;

        return new Transaction([
            'import_batch' => $this->batch,
            'item_id'     => $item->id,
            'user_id'     => $this->user?->id ?? auth()->id(),
            'bidang'      => $item->bidang,
            'no_normalisasi' => $isTeknik ? ($noNormalisasi ?: $item->no_normalisasi) : $item->no_normalisasi,
            'lokasi'      => $isTeknik ? ($this->cellString($row['lokasi'] ?? '') ?: $item->lokasi) : $item->lokasi,
            'volume'      => $isTeknik ? $item->volume : null,
            'ship_unloader' => $shipUnloader,
            'date'        => $date,
            'type'        => strtolower($this->cellString($row['jenis'] ?? '')),
            'quantity'    => $quantity,
            'price'       => $isTeknik ? null : $price,
            'description' => $this->cellString($row['keterangan'] ?? ''),
            'status'      => $item->bidang === 'teknik' ? 'approved' : 'pending',
            'approved_by' => $item->bidang === 'teknik' ? ($this->user?->id ?? auth()->id()) : null,
            'approved_at' => $item->bidang === 'teknik' ? now() : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required_without:no_normalisasi|nullable|max:255',
            'no_normalisasi' => 'required_without:nama_barang|nullable|max:255',
            'tanggal'     => 'required|date_format:Y-m-d|before_or_equal:today',
            'jenis'       => 'required|in:in,out',
            'jumlah'      => 'required_without:volume|nullable|integer|min:1',
            'harga'       => 'nullable|integer|min:0',
            'keterangan'  => 'nullable|max:500',
            'ship_unloader' => 'nullable|max:20',
            'lokasi'      => 'nullable|max:255',
            'volume'      => 'required_without:jumlah|nullable|integer|min:1',
            'satuan'      => 'nullable|max:50',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_barang.required' => 'Kolom nama_barang wajib diisi.',
            'nama_barang.required_without' => 'Kolom nama_barang atau no_normalisasi wajib diisi.',
            'no_normalisasi.required_without' => 'Kolom no_normalisasi atau nama_barang wajib diisi.',
            'tanggal.required'     => 'Kolom tanggal wajib diisi.',
            'tanggal.date_format'  => 'Tanggal tidak valid. Gunakan sel tanggal Excel atau format dd/mm/yyyy (mis. 17/04/2026).',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini (cek tertukar hari/bulan).',
            'harga.integer'        => 'Harga harus angka.',
            'jumlah.integer'       => 'Jumlah harus angka.',
            'jenis.required'       => 'Kolom jenis wajib diisi (in/out).',
            'jenis.in'             => 'Kolom jenis harus in/out (atau Masuk/Keluar).',
            'jumlah.required_without' => 'Kolom jumlah atau volume wajib diisi.',
            'jumlah.min'           => 'Jumlah minimal 1.',
            'volume.required_without' => 'Kolom volume atau jumlah wajib diisi.',
            'volume.min'           => 'Volume minimal 1.',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getDuplicateCount(): int
    {
        return $this->duplicateCount;
    }

    public function getUnmatchedItems(): array
    {
        return array_unique($this->unmatchedItems);
    }

    private function resolveItem(string $itemName, string $noNormalisasi): ?Item
    {
        $query = Item::visibleFor($this->user);

        if ($noNormalisasi !== '') {
            $item = (clone $query)->where('no_normalisasi', $noNormalisasi)->first();
            if ($item) {
                return $item;
            }
        }

        if ($itemName === '') {
            return null;
        }

        return $query->where('name', $itemName)->first();
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
