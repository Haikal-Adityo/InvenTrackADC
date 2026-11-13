<?php

namespace App\Support;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

/**
 * Kirim Web Push ke semua perangkat user. Dipicu otomatis setiap ada notifikasi
 * database baru (lihat AppServiceProvider), jadi isi push = isi notifikasi.
 * Badge ikon aplikasi = jumlah notifikasi yang belum dibaca.
 *
 * Aman bila library belum terpasang / kunci VAPID belum diisi: tidak melakukan apa-apa.
 */
final class WebPush
{
    public static function enabled(): bool
    {
        return class_exists(\Minishlink\WebPush\WebPush::class)
            && filled(config('webpush.public_key'))
            && filled(config('webpush.private_key'));
    }

    public static function fromDatabaseNotification(object $notifiable, mixed $record): void
    {
        if (! $notifiable instanceof User || ! $record instanceof DatabaseNotification || ! self::enabled()) {
            return;
        }

        $d = (array) $record->data;
        self::send($notifiable, [
            'title' => (string) ($d['title'] ?? 'NextLog'),
            'body' => (string) ($d['body'] ?? ''),
            'url' => self::withNotifId((string) ($d['url'] ?? ''), (string) $record->id),
            'tag' => 'nl-'.$record->id,
            'notificationId' => $record->id,
        ]);
    }

    /** URL absolut + ?nl_notif={id} (sebelum #hash) → klik notif di HP ikut menandai dibaca. */
    private static function withNotifId(string $url, string $id): string
    {
        $url = $url === '' || $url === '#' ? url('/dashboard') : url($url);
        [$base, $hash] = array_pad(explode('#', $url, 2), 2, null);
        $base .= (str_contains($base, '?') ? '&' : '?').'nl_notif='.rawurlencode($id);

        return $hash !== null ? $base.'#'.$hash : $base;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function send(User $user, array $payload): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $subs = PushSubscription::query()->where('user_id', $user->id)->get();
        } catch (\Throwable $e) {
            Log::warning('Web Push dilewati (tabel push_subscriptions belum siap): '.$e->getMessage());

            return;
        }
        if ($subs->isEmpty()) {
            return;
        }

        $payload['badge'] = $user->unreadNotifications()->count();
        $payload['icon'] = asset('images/pwa-icon-192.png');
        $payload['badgeIcon'] = asset('images/pwa-badge-96.png');

        try {
            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject' => config('webpush.subject'),
                    'publicKey' => config('webpush.public_key'),
                    'privateKey' => config('webpush.private_key'),
                ],
            ], ['TTL' => 86400, 'urgency' => 'high']);

            foreach ($subs as $s) {
                $webPush->queueNotification(
                    \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $s->endpoint,
                        'publicKey' => $s->public_key,
                        'authToken' => $s->auth_token,
                        'contentEncoding' => $s->content_encoding ?: 'aes128gcm',
                    ]),
                    json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    // Perangkat sudah mencabut izin / uninstall → hapus langganan
                    PushSubscription::query()
                        ->where('endpoint_hash', PushSubscription::hashEndpoint($report->getEndpoint()))
                        ->delete();
                } elseif (! $report->isSuccess()) {
                    Log::warning('Web Push gagal: '.$report->getReason(), ['user_id' => $user->id]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Web Push error: '.$e->getMessage(), ['user_id' => $user->id]);
        }
    }
}
