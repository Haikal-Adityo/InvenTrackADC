<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log aktivitas yang tidak tercatat di tabel lain:
 * perubahan master barang (tambah/ubah/hapus) dan cetak dokumen (PDF Rekap, dll).
 * Aktivitas transaksi, permintaan barang & stok request dibaca langsung dari tabelnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);                  // item_created, item_updated, item_deleted, document_printed
            $table->string('description')->nullable();
            $table->string('document_number', 50)->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
