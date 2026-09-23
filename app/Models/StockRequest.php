<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'bidang',
        'category',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function lines()
    {
        return $this->hasMany(StockRequestLine::class)->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVisibleFor($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('bidang', $user->bidang);
    }
}
