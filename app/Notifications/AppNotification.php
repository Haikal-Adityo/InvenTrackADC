<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notifikasi database generik: judul, isi, tautan (relatif, tanpa "/" di depan),
 * plus metadata (mis. kind, transaction_id) untuk menandai "sudah ditangani".
 * Setiap notifikasi ini otomatis ikut dikirim sebagai Web Push (lihat AppServiceProvider).
 */
class AppNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return array_merge($this->meta, [
            'title' => $this->title,
            'body' => $this->body,
            'url' => ltrim($this->url, '/'),
        ]);
    }
}
