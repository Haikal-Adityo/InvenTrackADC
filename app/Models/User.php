<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'bidang',
        'no_hp',
        'account_status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manajer';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staf';
    }

    public function isTeknik(): bool
    {
        return $this->bidang === 'teknik';
    }

    public function isUmum(): bool
    {
        return $this->bidang === 'umum';
    }

    public function isApproved(): bool
    {
        return $this->account_status === 'approved';
    }

    public function departmentLabel(): string
    {
        return match ($this->bidang) {
            'teknik' => 'Teknik',
            'umum' => 'Umum',
            default => 'Global',
        };
    }

    public function canAccessBidang(?string $bidang): bool
    {
        return $this->isSuperAdmin() || ($bidang !== null && $this->bidang === $bidang);
    }

    public function scopeVisibleFor($query, ?User $user = null)
    {
        $user ??= auth()->user();

        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('bidang', $user->bidang);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function approvedTransactions()
    {
        return $this->hasMany(Transaction::class, 'approved_by');
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
