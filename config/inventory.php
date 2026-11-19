<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alamat email tambahan untuk notifikasi admin
    |--------------------------------------------------------------------------
    |
    | Opsional. Jika diisi, email alert (transaksi baru & stok rendah) juga
    | dikirim ke alamat ini, selain ke semua user ber-role admin.
    | Berguna jika Anda ingin menerima salinan di inbox pribadi.
    |
    */

    'alert_mail' => env('INVENTORY_ALERT_MAIL'),

];
