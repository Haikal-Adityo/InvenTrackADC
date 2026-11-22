<?php

namespace App\Exports;

use App\Models\StockRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockRequestExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithStrictNullComparison, WithColumnFormatting
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
            'No. Request',
            'Tanggal',
            'Pemohon',
            'Barang',
            'Kategori',
            'Jumlah',
            'Satuan',
            'Harga Satuan',
            'Subtotal',
            'Keterangan',
            'Status',
            'Diproses Oleh',
            'Tanggal Proses',
        ];
    }

    public function map($row): array
    {
        return [
            $row->request->document_number,
            $row->request->created_at->format('d/m/Y H:i'),
            $row->request->user->name ?? '-',
            $row->line->item->name ?? '-',
            $row->request->category ?: ($row->line->category ?: ($row->line->item->category ?? '-')),
            (int) $row->line->quantity,
            $row->line->item->unit ?? '-',
            (int) $row->line->price,
            (int) $row->line->price * (int) $row->line->quantity,
            filled($row->line->description) ? $row->line->description : '-',
            strtoupper($row->request->status_label),
            $row->request->processor->name ?? '-',
            $row->request->processed_at ? $row->request->processed_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function columnFormats(): array
    {
        $rupiah = '"Rp" #,##0';

        return [
            'F' => '#,##0',
            'H' => $rupiah,
            'I' => $rupiah,
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
