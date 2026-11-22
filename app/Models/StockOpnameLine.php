<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameLine extends Model
{
    protected $fillable = ['stock_opname_id', 'item_id', 'system_stock', 'actual_stock', 'difference', 'notes', 'adjustment_transaction_id'];

    protected function casts(): array
    {
        return ['system_stock' => 'integer', 'actual_stock' => 'integer', 'difference' => 'integer'];
    }

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function adjustment()
    {
        return $this->belongsTo(Transaction::class, 'adjustment_transaction_id');
    }
}
