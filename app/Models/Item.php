<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'no_normalisasi',
        'category',
        'component',
        'unit',
        'bidang',
        'lokasi',
        'volume',
        'ship_unloader',
        'min_stock',
    ];

    /** Perubahan master barang dicatat ke Log Aktivitas. */
    protected static function booted(): void
    {
        $labels = [
            'name' => 'Nama', 'category' => 'Kategori', 'unit' => 'Satuan', 'min_stock' => 'Minimum',
            'lokasi' => 'Lokasi', 'component' => 'Komponen', 'no_normalisasi' => 'No Normalisasi', 'bidang' => 'Bidang',
        ];

        static::created(fn (Item $item) => ActivityLog::record('item_created', 'Barang ditambahkan ke master', $item->id));

        static::updated(function (Item $item) use ($labels) {
            $changes = collect($item->getChanges())->except(['updated_at', 'created_at', 'deleted_at'])
                ->filter(fn ($v, $k) => isset($labels[$k]))
                ->map(fn ($v, $k) => $labels[$k].': '.($item->getOriginal($k) ?? '-').' → '.($v ?? '-'));
            if ($changes->isNotEmpty()) {
                ActivityLog::record('item_updated', $changes->implode('; '), $item->id);
            }
        });

        static::deleted(fn (Item $item) => ActivityLog::record('item_deleted', 'Barang dihapus dari master', $item->id));
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getCurrentStockAttribute(): int
    {
        $masuk = $this->transactions()
            ->where('type', 'in')
            ->where('status', 'approved')
            ->sum('quantity');

        $keluar = $this->transactions()
            ->where('type', 'out')
            ->where('status', 'approved')
            ->sum('quantity');

        return $masuk - $keluar;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    public function getShipUnloaderLabelAttribute(): string
    {
        return self::formatShipUnloader($this->ship_unloader);
    }

    public function getStockShipUnloaderAttribute(): ?string
    {
        return $this->ship_unloader;
    }

    public function getStockShipUnloaderLabelAttribute(): string
    {
        return self::formatShipUnloader($this->stock_ship_unloader);
    }

    public static function mergeShipUnloaders(array $values): ?string
    {
        $ships = collect($values)
            ->flatMap(fn($value) => explode(',', (string) $value))
            ->map(fn($ship) => trim($ship))
            ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();

        return $ships->isEmpty() ? null : $ships->implode(',');
    }

    public static function formatShipUnloader(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        $ships = collect(explode(',', $value))
            ->map(fn($ship) => trim($ship))
            ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();

        return $ships->isEmpty() ? '-' : $ships->implode(' - ');
    }

    public function scopeLowStock($query)
    {
        return $query->get()->filter(function ($item) {
            return $item->is_low_stock;
        });
    }

    public function scopeVisibleFor($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('bidang', $user->bidang);
    }

    public function getCurrentShipUnloaderAttribute(): ?string
    {
        $activeShips = [];

        $transactions = $this->transactions()
            ->where('status', 'approved')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $tx) {

            $ships = collect(explode(',', $tx->ship_unloader))
                ->map(fn($s) => trim($s))
                ->filter()
                ->toArray();

            if ($tx->type === 'in') {

                foreach ($ships as $ship) {
                    $activeShips[$ship] = true;
                }

            } elseif ($tx->type === 'out') {

                foreach ($ships as $ship) {
                    unset($activeShips[$ship]);
                }
            }
        }

        return empty($activeShips)
            ? null
            : collect(array_keys($activeShips))
                ->sort()
                ->implode(',');
    }

    public function getCurrentShipUnloaderLabelAttribute(): string
    {
        return self::formatShipUnloader(
            $this->current_ship_unloader
        );
    }

    public function applyShipUnloader(?string $shipUnloader): void
    {
        if ($this->bidang !== 'teknik') {
            return;
        }

        if ($this->ship_unloader !== $shipUnloader) {
            $this->forceFill(['ship_unloader' => $shipUnloader])->saveQuietly();
        }
    }

    public function refreshShipUnloaderFromLatestTransaction(): void
    {
        if ($this->bidang !== 'teknik') {
            return;
        }

        $this->applyShipUnloader($this->current_ship_unloader);
    }
}
