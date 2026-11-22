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
        'need_start',
        'need_end',
        'status',
        'verified_by',
        'verified_at',
        'processed_by',
        'processed_at',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Menunggu Verifikasi',
        'verified' => 'Menunggu Persetujuan',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'verified_at' => 'datetime',
            'need_start' => 'date',
            'need_end' => 'date',
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

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Alur 2 tahap (verifikasi admin → persetujuan manajer) hanya untuk bidang Umum. */
    public function usesTwoStepApproval(): bool
    {
        return $this->bidang !== 'teknik';
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->bidang === 'teknik' && $this->status === 'pending') {
            return 'Pending';
        }

        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getNeedPeriodLabelAttribute(): ?string
    {
        if (! $this->need_start && ! $this->need_end) {
            return null;
        }

        // Rentang bulan penuh → "Desember 2026" atau "November – Desember 2026"
        if ($this->need_start && $this->need_end
            && $this->need_start->isSameDay($this->need_start->copy()->startOfMonth())
            && $this->need_end->isSameDay($this->need_end->copy()->endOfMonth())) {
            if ($this->need_start->isSameMonth($this->need_end)) {
                return $this->need_start->translatedFormat('F Y');
            }

            return $this->need_start->year === $this->need_end->year
                ? $this->need_start->translatedFormat('F').' – '.$this->need_end->translatedFormat('F Y')
                : $this->need_start->translatedFormat('F Y').' – '.$this->need_end->translatedFormat('F Y');
        }

        $fmt = fn ($d) => $d ? $d->translatedFormat('d M Y') : '…';

        return $fmt($this->need_start).' – '.$fmt($this->need_end);
    }

    /**
     * Nomor dokumen: ADC-{yy}{bulan romawi}STR{id 3 digit}, mis. ADC-26XISTR015.
     */
    public function getDocumentNumberAttribute(): string
    {
        $created = $this->created_at ?? now();
        $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

        return 'ADC-' . $created->format('y') . $romawi[$created->month] . 'STR' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /** Belum selesai diproses (pending atau sudah diverifikasi, menunggu persetujuan). */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'verified']);
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
