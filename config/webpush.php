<?php

/*
 * Web Push (notifikasi HP walau aplikasi tertutup + badge angka di ikon aplikasi).
 * Buat kunci sekali: php artisan webpush:vapid  → salin ke .env (lokal & production SAMA).
 * Kalau kunci diganti, semua perangkat harus mendaftar ulang.
 */
return [
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
    'subject' => env('VAPID_SUBJECT', 'mailto:'.env('MAIL_FROM_ADDRESS', 'admin@example.com')),

    // Role yang perangkatnya didaftarkan untuk push
    'roles' => ['superadmin', 'admin', 'manajer', 'staf'],
];
