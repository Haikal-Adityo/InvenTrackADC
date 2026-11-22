<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock Opname (SO): staf/admin menghitung fisik barang → admin verifikasi →
 * selisih otomatis dibuat transaksi penyesuaian (IN untuk lebih, OUT untuk kurang).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_opnames')) {
            Schema::create('stock_opnames', function (Blueprint $table) {
                $table->id();
                $table->string('bidang', 20)->default('umum');
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();   // petugas SO
                $table->timestamp('counted_at');                                                   // tanggal & jam SO
                $table->enum('status', ['submitted', 'verified', 'rejected'])->default('submitted');
                $table->text('notes')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stock_opname_lines')) {
            Schema::create('stock_opname_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->integer('system_stock');      // stok sistem saat dihitung
                $table->integer('actual_stock');      // hasil hitung fisik
                $table->integer('difference');        // aktual - sistem
                $table->string('notes', 500)->nullable();
                $table->foreignId('adjustment_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_lines');
        Schema::dropIfExists('stock_opnames');
    }
};
