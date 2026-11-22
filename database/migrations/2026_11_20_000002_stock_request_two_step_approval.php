<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revisi 20 Nov 2026 — Stok Request:
 * - Alur Umum 2 tahap: pending (menunggu verifikasi admin) → verified (menunggu persetujuan manajer) → approved/rejected
 * - Periode kebutuhan (rentang tanggal)
 * - Referensi per baris = Stok Request approved terakhir yang berisi barang tsb
 * - Pengaturan teks PDF (app_settings)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('pending','verified','approved','rejected') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('stock_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_requests', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('stock_requests', 'need_start')) {
                $table->date('need_start')->nullable()->after('category');
                $table->date('need_end')->nullable()->after('need_start');
            }
        });

        Schema::table('stock_request_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_request_lines', 'reference_stock_request_id')) {
                $table->foreignId('reference_stock_request_id')->nullable()->after('stock_request_id')
                    ->constrained('stock_requests')->nullOnDelete();
            }
        });

        if (! Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');

        Schema::table('stock_request_lines', function (Blueprint $table) {
            if (Schema::hasColumn('stock_request_lines', 'reference_stock_request_id')) {
                $table->dropConstrainedForeignId('reference_stock_request_id');
            }
        });

        Schema::table('stock_requests', function (Blueprint $table) {
            if (Schema::hasColumn('stock_requests', 'verified_by')) {
                $table->dropConstrainedForeignId('verified_by');
                $table->dropColumn('verified_at');
            }
            if (Schema::hasColumn('stock_requests', 'need_start')) {
                $table->dropColumn(['need_start', 'need_end']);
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("UPDATE stock_requests SET status = 'pending' WHERE status = 'verified'");
            DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
