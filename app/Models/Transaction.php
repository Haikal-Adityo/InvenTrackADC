<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'user_id',
        'stuff_request_id',
        'bidang',
        'no_normalisasi',
        'lokasi',
        'volume',
        'ship_unloader',
        'date',
        'type',
        'quantity',
        'price',
        'description',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Transaction $transaction): void {
            if ($transaction->bidang !== 'teknik' || !$transaction->item_id) {
                return;
            }

            $item = $transaction->relationLoaded('item')
                ? $transaction->item
                : Item::withTrashed()->find($transaction->item_id);

            if (!$item) {
                return;
            }

            $item->refreshShipUnloaderFromLatestTransaction();
        });

        static::deleted(function (Transaction $transaction): void {
            if ($transaction->bidang !== 'teknik' || !$transaction->item_id) {
                return;
            }

            Item::withTrashed()->find($transaction->item_id)?->refreshShipUnloaderFromLatestTransaction();
        });
    }

    public function stuffRequest()
    {
        return $this->belongsTo(StuffRequest::class)->withTrashed();
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeMasuk($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeKeluar($query)
    {
        return $query->where('type', 'out');
    }

    public function scopeVisibleFor($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('bidang', $user->bidang);
    }

    public function getShipUnloaderLabelAttribute(): string
    {
        return Item::formatShipUnloader($this->ship_unloader);
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->bidang === 'teknik') {
            return $this->type === 'in' ? 'Goods Receipt (GR)' : 'Goods Issue (GI)';
        }

        return strtoupper($this->type);
    }
}
