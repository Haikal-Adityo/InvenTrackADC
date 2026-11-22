<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Penanda batch import Excel → bisa dibatalkan per batch & mencegah import file yang sama dua kali. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'import_batch')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('import_batch', 40)->nullable()->after('stuff_request_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transactions', 'import_batch')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['import_batch']);
                $table->dropColumn('import_batch');
            });
        }
    }
};
