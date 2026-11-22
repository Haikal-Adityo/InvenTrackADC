<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = ['bidang', 'user_id', 'counted_at', 'status', 'notes', 'verified_by', 'verified_at'];

    public const STATUS_LABELS = [
        'submitted' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];

    protected function casts(): array
    {
        return ['counted_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function lines()
    {
        return $this->hasMany(StockOpnameLine::class)->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVisibleFor($query, ?User $user = null)
    {
        $user ??= auth()->user();

        return (! $user || $user->isSuperAdmin()) ? $query : $query->where('bidang', $user->bidang);
    }

    /** ADC-{yy}{bulan romawi}SO{id 3 digit} */
    public function getDocumentNumberAttribute(): string
    {
        $at = $this->counted_at ?? $this->created_at ?? now();
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

        return 'ADC-'.$at->format('y').$romawi[$at->month].'SO'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
