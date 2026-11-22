<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRequestLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_request_id',
        'reference_stock_request_id',
        'item_id',
        'quantity',
        'need_month',
        'price',
        'category',
        'description',
    ];

    protected function casts(): array
    {
        return ['need_month' => 'date'];
    }

    public function getNeedMonthLabelAttribute(): ?string
    {
        return $this->need_month?->translatedFormat('F Y');
    }

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function referenceRequest()
    {
        return $this->belongsTo(StockRequest::class, 'reference_stock_request_id')->withTrashed();
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
