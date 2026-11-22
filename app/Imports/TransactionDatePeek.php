<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithColumnLimit;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Baca cepat kolom tanggal untuk menebak urutan tanggal teks (d/m atau m/d) sebelum import. */
class TransactionDatePeek implements ToArray, WithHeadingRow, WithColumnLimit
{
    public array $dates = [];

    public function array(array $rows): void
    {
        foreach ($rows as $row) {
            if (isset($row['tanggal'])) {
                $this->dates[] = $row['tanggal'];
            }
        }
    }

    public function endColumn(): string
    {
        return 'Z';
    }
}
