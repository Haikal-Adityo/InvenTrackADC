<?php

use App\Mail\DailyStockAlertDigest;
use App\Mail\DailyTransactionDigest;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use App\Support\InventoryMail;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notify:daily-digest', function () {
    $bidang = 'umum';
    $recipients = InventoryMail::adminNotificationRecipients($bidang);

    if ($recipients === []) {
        $this->warn('Tidak ada alamat admin bidang umum. Skipping digest.');
        return 0;
    }

    $today = Carbon::today();
    $dateLabel = $today->translatedFormat('l, d F Y');
    $sent = 0;

    $pendingTransactions = Transaction::with(['item', 'user'])
        ->where('bidang', $bidang)
        ->where('status', 'pending')
        ->whereDate('created_at', $today)
        ->latest()
        ->get();

    if ($pendingTransactions->isNotEmpty()) {
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DailyTransactionDigest($pendingTransactions, $dateLabel));
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Gagal kirim digest transaksi umum', ['to' => $email, 'error' => $e->getMessage()]);
            }
        }
        $this->info("Email digest transaksi umum ({$pendingTransactions->count()} transaksi) dikirim ke {$sent} penerima.");
    } else {
        $this->info('Tidak ada transaksi pending umum hari ini. Email transaksi tidak dikirim.');
    }

    $allItems = Item::where('bidang', $bidang)->get();
    $outOfStock = $allItems->filter(fn($item) => $item->current_stock === 0 && $item->min_stock > 0);
    $lowStock = $allItems->filter(fn($item) => $item->current_stock > 0 && $item->current_stock < $item->min_stock);

    if ($outOfStock->isNotEmpty() || $lowStock->isNotEmpty()) {
        $sentStock = 0;
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new DailyStockAlertDigest($outOfStock, $lowStock, $dateLabel));
                $sentStock++;
            } catch (\Throwable $e) {
                Log::error('Gagal kirim digest stok umum', ['to' => $email, 'error' => $e->getMessage()]);
            }
        }
        $total = $outOfStock->count() + $lowStock->count();
        $this->info("Email digest stok umum ({$total} barang: {$outOfStock->count()} habis, {$lowStock->count()} rendah) dikirim ke {$sentStock} penerima.");
    } else {
        $this->info('Semua stok umum aman. Email stok tidak dikirim.');
    }

    return 0;
})->purpose('Kirim email digest harian admin umum (transaksi pending + stock alert)');

Schedule::command('notify:daily-digest')
    ->dailyAt('15:00')
    ->timezone(config('app.timezone', 'Asia/Jakarta'))
    ->withoutOverlapping();

Artisan::command('mail:test {email? : Alamat tujuan (default: email user admin umum pertama)}', function () {
    $email = $this->argument('email');
    if (!is_string($email) || trim($email) === '') {
        $admin = User::query()
            ->where('role', 'admin')
            ->where('bidang', 'umum')
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();

        if (!$admin) {
            $this->error('Tidak ada user admin umum dengan email. Jalankan: php artisan mail:test anda@email.com');

            return 1;
        }

        $email = $admin->email;
    }

    try {
        Mail::raw(
            'Ini email percobaan dari ' . InventoryMail::appName() . '. Waktu server: ' . now()->toDateTimeString() . "\n\nJika Anda membaca ini, SMTP sudah terkonfigurasi benar.",
            function ($message) use ($email) {
                $message->to($email)->subject('[' . InventoryMail::appName() . '] Tes pengiriman email');
            }
        );
    } catch (\Throwable $e) {
        $this->error('Gagal mengirim: ' . $e->getMessage());
        $this->line('Periksa MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, dan MAIL_FROM_ADDRESS.');
        $this->line('Lihat log: storage/logs/laravel.log');

        return 1;
    }

    $this->info("Email tes terkirim ke {$email}. Periksa inbox dan folder spam.");

    return 0;
})->purpose('Uji koneksi SMTP / pengiriman email');

/*
 * Buat pasangan kunci VAPID untuk Web Push. Jalankan SEKALI, lalu salin ke .env
 * lokal dan production (harus sama; kalau berubah, semua perangkat harus daftar ulang).
 */
Artisan::command('webpush:vapid', function () {
    if (! class_exists(\Minishlink\WebPush\VAPID::class)) {
        $this->error('Jalankan dulu: composer require minishlink/web-push');

        return 1;
    }
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
    $this->line('Tambahkan ke .env:');
    $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
    $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
    $this->line('VAPID_SUBJECT=mailto:'.config('mail.from.address'));

    return 0;
})->purpose('Buat kunci VAPID untuk Web Push');

/*
 * Notifikasi dummy untuk uji (push HP + badge ikon).
 * Contoh: php artisan notif:test adminumum --count=3 --url=/dashboard
 */
Artisan::command('notif:test {username : Username penerima} {--count=1} {--url=/dashboard}', function () {
    $user = User::where('username', $this->argument('username'))->first();
    if (! $user) {
        $this->error('User tidak ditemukan.');

        return 1;
    }
    $n = max(1, (int) $this->option('count'));
    for ($i = 1; $i <= $n; $i++) {
        $user->notify(new \App\Notifications\AppNotification(
            'Tes Notifikasi NextLog #'.$i,
            'Ini notifikasi dummy pukul '.now()->format('H:i:s').'.',
            (string) $this->option('url'),
            ['kind' => 'test'],
        ));
    }
    $devices = \App\Models\PushSubscription::where('user_id', $user->id)->count();
    $this->info("Terkirim {$n} notifikasi ke {$user->username} ("
        .(\App\Support\WebPush::enabled() ? "push ke {$devices} perangkat)" : 'push nonaktif: library/VAPID belum siap)').'.');

    return 0;
})->purpose('Kirim notifikasi dummy ke user (uji push/badge)');

/* Hapus notifikasi dummy: php artisan notif:clear [username] [--all] */
Artisan::command('notif:clear {username?} {--all : hapus SEMUA notifikasi user, bukan hanya dummy}', function () {
    $q = \Illuminate\Notifications\DatabaseNotification::query();
    if ($u = $this->argument('username')) {
        $user = User::where('username', $u)->first();
        if (! $user) {
            $this->error('User tidak ditemukan.');

            return 1;
        }
        $q->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->id);
    }
    if (! $this->option('all')) {
        $q->where('data', 'like', '%"kind":"test"%');
    }
    $this->info($q->delete().' notifikasi dihapus.');

    return 0;
})->purpose('Hapus notifikasi dummy');
