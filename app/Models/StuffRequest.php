<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class StuffRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stuff_requests';

    protected $fillable = [
        'requester_name',
        'nip',
        'jabatan',
        'bidang',
        'notes',
        'status',
        'processed_by',
        'processed_at',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function lines()
    {
        return $this->hasMany(StuffRequestItem::class)->orderBy('id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancel');
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
