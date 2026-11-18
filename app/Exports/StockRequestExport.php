<?php

namespace App\Exports;

use App\Models\StockRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockRequestExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * @param Builder $query Query yang sudah difilter (hak akses + filter halaman/modal) dari StockRequestController.
     */
    public function __construct(private Builder $query)
    {
    }

    public function collection(): Collection
    {
        return $this->query
            ->get()
            ->flatMap(function (StockRequest $stockRequest) {
                return $stockRequest->lines->map(fn($line) => (object) [
                    'request' => $stockRequest,
                    'line' => $line,
                ]);
            })
            ->values();
    }

    public function headings(): array
    {
        return [
            'ID Request',
            'Tanggal',
            'Pemohon',
            'Barang',
            'Kategori',
            'Harga Barang',
            'Jumlah',
            'Keterangan',
            'Status',
            'Diproses Oleh',
            'Tanggal Proses',
        ];
    }

    public function map($row): array
    {
        return [
            $row->request->id,
            $row->request->created_at->format('d/m/Y H:i'),
            $row->request->user->name ?? '-',
            $row->line->item->name ?? '-',
            $row->request->category ?: ($row->line->category ?: ($row->line->item->category ?? '-')),
            $row->line->price,
            $row->line->quantity,
            $row->line->description ?? '-',
            strtoupper($row->request->status),
            $row->request->processor->name ?? '-',
            $row->request->processed_at ? $row->request->processed_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'],
                ],
            ],
        ];
    }
}
