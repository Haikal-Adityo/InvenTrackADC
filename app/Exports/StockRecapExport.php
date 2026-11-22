<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockRecapExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;
    protected $year;
    protected $category;
    protected $search;
    protected $stockStatus;

    public function __construct(
        $dateFrom = null,
        $dateTo = null,
        $year = null,
        $category = null,
        $search = null,
        $stockStatus = null
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->year = $year;
        $this->category = $category;
        $this->search = $search;
        $this->stockStatus = $stockStatus;
    }

    public function collection(): Collection
    {
        $bounds = $this->dateBounds();
        $items = Item::query()
            ->visibleFor(auth()->user())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('category', 'like', "%{$this->search}%")
                        ->orWhere('component', 'like', "%{$this->search}%")
                        ->orWhere('no_normalisasi', 'like', "%{$this->search}%")
                        ->orWhere('lokasi', 'like', "%{$this->search}%")
                        ->orWhere('unit', 'like', "%{$this->search}%");
                });
            })
            ->when($this->category, fn($query) => $query->where($this->isTeknikReport() ? 'component' : 'category', $this->category))
            ->orderBy('name')
            ->get();

        $rows = $items->map(function (Item $item) use ($bounds) {
            $stokAwal = 0;
            if ($bounds['from']) {
                $stokAwal = $item->transactions()->whereDate('date', '<', $bounds['from'])->approved()->masuk()->sum('quantity')
                    - $item->transactions()->whereDate('date', '<', $bounds['from'])->approved()->keluar()->sum('quantity');
            }

            $masukQuery = $item->transactions()->approved()->masuk();
            $keluarQuery = $item->transactions()->approved()->keluar();

            if ($bounds['from']) {
                $masukQuery->whereDate('date', '>=', $bounds['from']);
                $keluarQuery->whereDate('date', '>=', $bounds['from']);
            }

            if ($bounds['to']) {
                $masukQuery->whereDate('date', '<=', $bounds['to']);
                $keluarQuery->whereDate('date', '<=', $bounds['to']);
            }

            $masuk = $masukQuery->sum('quantity');
            $keluar = $keluarQuery->sum('quantity');
            $stokAkhir = $stokAwal + $masuk - $keluar;

            return (object) [
                'item' => $item,
                'stok_awal' => $stokAwal,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'stok_akhir' => $stokAkhir,
            ];
        });

        if ($this->stockStatus) {
            $rows = $rows->filter(function ($row) {
                return match ($this->stockStatus) {
                    'empty' => $row->stok_akhir <= 0,
                    'low' => $row->stok_akhir <= $row->item->min_stock,
                    'ready' => $row->stok_akhir > $row->item->min_stock,
                    default => true,
                };
            });
        }

        return $rows->values();
    }

    public function headings(): array
    {
        if ($this->isTeknikReport()) {
            return [
                'No',
                'No Normalisasi',
                'Nama Barang',
                'Kategori',
                'Komponen',
                'Ship Unloader',
                'Lokasi',
                'Volume',
                'Satuan',
                'Total Goods Receipt',
                'Total Goods Issue',
                'Stok',
                'Minimum',
                'Status',
            ];
        }

        return [
            'No',
            'Nama Barang',
            'Kategori',
            'Satuan',
            'Total Masuk',
            'Total Keluar',
            'Stok',
            'Minimum',
            'Status',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        if ($this->isTeknikReport()) {
            return [
                $no,
                $row->item->no_normalisasi ?? '-',
                $row->item->name,
                $row->item->category,
                $row->item->component ?? '-',
                $row->item->stock_ship_unloader_label,
                $row->item->lokasi ?? '-',
                $this->visibleNumber($row->item->volume),
                $row->item->unit,
                $this->visibleNumber($row->masuk),
                $this->visibleNumber($row->keluar),
                $this->visibleNumber($row->stok_akhir),
                $this->visibleNumber($row->item->min_stock),
                $this->stockStatusLabel($row),
            ];
        }

        return [
            $no,
            $row->item->name,
            $row->item->category,
            $row->item->unit,
            $this->visibleNumber($row->masuk),
            $this->visibleNumber($row->keluar),
            $this->visibleNumber($row->stok_akhir),
            $this->visibleNumber($row->item->min_stock),
            $this->stockStatusLabel($row),
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

    private function dateBounds(): array
    {
        $from = $this->dateFrom;
        $to = $this->dateTo;

        if ($this->year) {
            $from = $from ?: $this->year . '-01-01';
            $to = $to ?: $this->year . '-12-31';
        }

        return ['from' => $from, 'to' => $to];
    }

    private function stockStatusLabel($row): string
    {
        if ($row->stok_akhir <= 0) {
            return 'Out of Stock';
        }

        if ($row->stok_akhir <= $row->item->min_stock) {
            return 'Request Order';
        }

        return 'Ready';
    }

    private function visibleNumber($value)
    {
        return (int) $value === 0 ? '0' : $value;
    }

    private function isTeknikReport(): bool
    {
        return auth()->user()?->bidang === 'teknik';
    }
}
