<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Replaces the original generic (table_name/record_id/old_values/new_values) audit_logs
// shape with the readable one the Audit Logs page actually uses: module/title/details/
// description/diff/ip_address. This alters the existing table instead of a second
// Schema::create, since 2026_01_01_000004_create_audit_logs_table already created it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action VARCHAR(255) NOT NULL");

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['table_name', 'record_id', 'old_values', 'new_values']);

            $table->string('module')->after('action');
            $table->string('title')->after('module');
            $table->text('details')->nullable()->after('title');
            $table->text('description')->nullable()->after('details');
            $table->json('diff')->nullable()->after('description');
            $table->string('ip_address', 45)->nullable()->after('diff');
            $table->timestamp('updated_at')->nullable()->after('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['module', 'title', 'details', 'description', 'diff', 'ip_address', 'updated_at']);
        });

        DB::statement("ALTER TABLE audit_logs MODIFY COLUMN action ENUM('create','update','delete') NOT NULL");

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('table_name', 50)->after('user_id');
            $table->unsignedBigInteger('record_id')->after('table_name');
            $table->json('old_values')->nullable()->after('action');
            $table->json('new_values')->nullable()->after('old_values');

            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
