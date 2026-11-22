<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hubungkan transaksi OUT otomatis ke Permintaan Barang asalnya,
 * supaya saat permintaan dihapus transaksinya ikut terhapus (stok kembali).
 * Data lama diisi dari teks keterangan "Otomatis dibuat dari Permintaan Barang ID: X".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'stuff_request_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('stuff_request_id')->nullable()->after('user_id')
                    ->constrained('stuff_requests')->cascadeOnDelete();
            });
        }

        DB::table('transactions')
            ->whereNull('stuff_request_id')
            ->where('description', 'like', 'Otomatis dibuat dari Permintaan Barang ID:%')
            ->orderBy('id')
            ->get(['id', 'description'])
            ->each(function ($tx) {
                if (preg_match('/Permintaan Barang ID:\s*(\d+)/', (string) $tx->description, $m)
                    && DB::table('stuff_requests')->where('id', (int) $m[1])->exists()) {
                    DB::table('transactions')->where('id', $tx->id)->update(['stuff_request_id' => (int) $m[1]]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'stuff_request_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('stuff_request_id');
            });
        }
    }
};
