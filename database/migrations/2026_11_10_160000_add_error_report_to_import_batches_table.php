<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Row-level import validation (TC-05): bad rows (no APP. NO., unreadable SEX, no section
// to classify under) are skipped instead of silently saved with wrong/defaulted data, and
// reported here instead of just disappearing.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->unsignedInteger('error_count')->nullable()->after('row_count');
            $table->json('errors')->nullable()->after('error_count');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(['error_count', 'errors']);
        });
    }
};
