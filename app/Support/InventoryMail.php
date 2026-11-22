<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     * Email approver sesuai alur approval, diambil dari database (bukan .env):
     * - Umum  → semua user role "admin" bidang umum
     * - Teknik → semua user role "manajer" bidang teknik
     * - Superadmin ikut menerima email bidang Umum
     * Hanya akun berstatus approved dengan email valid.
     *
     * @return list<string>
     */
    public static function approverRecipients(?string $bidang): array
    {
        $bidang = $bidang ?: 'umum';
        $role = $bidang === 'teknik' ? 'manajer' : 'admin';

        return self::emailsOf(
            User::query()
                ->where(function ($q) use ($role, $bidang) {
                    $q->where(fn ($q) => $q->where('role', $role)->where('bidang', $bidang));
                    // Superadmin ikut menerima email approval bidang Umum
                    if ($bidang === 'umum') {
                        $q->orWhere('role', 'superadmin');
                    }
                })
                ->where('account_status', 'approved')
                ->whereNotNull('email')
                ->pluck('email')
        );
    }

    /**
     * Email manajer bidang (penyetuju tahap 2 Stok Request Umum).
     *
     * @return list<string>
     */
    public static function managerRecipients(?string $bidang): array
    {
        return self::emailsOf(
            User::query()
                ->where('role', 'manajer')
                ->where('bidang', $bidang ?: 'umum')
                ->where('account_status', 'approved')
                ->whereNotNull('email')
                ->pluck('email')
        );
    }

    /**
     * Kirim mailable ke tiap penerima (satu email per alamat), error dicatat ke log.
     *
     * @param  list<string>  $recipients
     */
    public static function sendTo(array $recipients, Mailable $mail, string $context, array $meta = []): void
    {
        if ($recipients === []) {
            Log::warning("Email {$context} tidak dikirim: tidak ada penerima (cek user role/bidang/email/account_status di database).", $meta);
            return;
        }

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send($mail);
            } catch (\Throwable $e) {
                Log::error("Gagal kirim email {$context}", $meta + ['to' => $email, 'message' => $e->getMessage()]);
            }
        }
    }

    /**
     * @param  Collection<int, string|null>  $emails
     * @return list<string>
     */
    private static function emailsOf(Collection $emails): array
    {
        return $emails
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn (string $e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
