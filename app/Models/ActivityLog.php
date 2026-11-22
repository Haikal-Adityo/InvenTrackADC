<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['item_id', 'user_id', 'action', 'description', 'document_number', 'meta', 'created_at'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function item()
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Catat aktivitas; kegagalan tidak boleh mengganggu proses utama. */
    public static function record(string $action, ?string $description = null, ?int $itemId = null, ?string $documentNumber = null, array $meta = []): void
    {
        try {
            static::query()->create([
                'item_id' => $itemId,
                'user_id' => auth()->id(),
                'action' => $action,
                'description' => $description ? mb_substr($description, 0, 255) : null,
                'document_number' => $documentNumber,
                'meta' => $meta ?: null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal mencatat activity log', ['action' => $action, 'message' => $e->getMessage()]);
        }
    }
}
