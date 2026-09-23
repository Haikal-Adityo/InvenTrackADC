<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class InventoryMail
{
    public static function appName(): string
    {
        return (string) config('app.name', 'nextlogistic');
    }

    /**
     * Alamat unik untuk notifikasi admin (role admin + opsional INVENTORY_ALERT_MAIL).
     *
     * @return list<string>
     */
    public static function adminNotificationRecipients(?string $bidang = null): array
    {
        /** @var Collection<int, string> $fromDb */
        $fromDb = User::query()
            ->where('role', 'admin')
            ->when($bidang, fn($query) => $query->where('bidang', $bidang))
            ->whereNotNull('email')
            ->pluck('email');

        $emails = $fromDb
            ->map(fn (string $e) => strtolower(trim($e)))
            ->filter(fn (string $e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL));

        $extra = config('inventory.alert_mail');
        if (is_string($extra)) {
            $extra = strtolower(trim($extra));
            if ($extra !== '' && filter_var($extra, FILTER_VALIDATE_EMAIL)) {
                $emails->push($extra);
            }
        }

        return $emails->unique()->values()->all();
    }

    /**
     * Alamat notifikasi GAADM (config inventory.gaadm_mail / env GAADM_NOTIFICATION_MAIL).
     * Sementara diarahkan ke email testing, nanti diganti ke email GAADM asli.
     *
     * @return list<string>
     */
    public static function gaadmNotificationRecipients(): array
    {
        $email = config('inventory.gaadm_mail');
        if (!is_string($email)) {
            return [];
        }

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }

        return [$email];
    }
}
