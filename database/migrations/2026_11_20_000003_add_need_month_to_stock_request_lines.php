<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Periode Kebutuhan per barang (disimpan sebagai tanggal 1 bulan tsb). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_request_lines', 'need_month')) {
            Schema::table('stock_request_lines', function (Blueprint $table) {
                $table->date('need_month')->nullable()->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_request_lines', 'need_month')) {
            Schema::table('stock_request_lines', function (Blueprint $table) {
                $table->dropColumn('need_month');
            });
        }
    }
};
