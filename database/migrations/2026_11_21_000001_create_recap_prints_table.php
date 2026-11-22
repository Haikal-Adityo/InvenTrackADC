<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat PDF Rekap Persediaan Barang.
 * Setiap klik "Export PDF" = 1 PDF baru: nomor urut global (cetakan ke-N) + file disimpan.
 * Membuka ulang link PDF yang sama tidak menambah hitungan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pengganti tabel document_prints (draft sebelumnya, belum pernah dipakai di production)
        Schema::dropIfExists('document_prints');

        if (! Schema::hasTable('recap_prints')) {
            Schema::create('recap_prints', function (Blueprint $table) {
                $table->id();                                   // = cetakan ke-N (global)
                $table->string('document_number', 50)->nullable()->unique();
                $table->string('bidang', 20)->nullable();
                $table->json('filters')->nullable();
                $table->unsignedInteger('row_count')->default(0);
                $table->unsignedBigInteger('grand_total')->default(0);
                $table->string('file_path')->nullable();
                $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recap_prints');
    }
};
