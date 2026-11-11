<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicate = DB::table('applicants')->whereNull('archived_at')->whereNotNull('external_app_no')
            ->select('school_year', 'external_app_no')->groupBy('school_year', 'external_app_no')->havingRaw('COUNT(*) > 1')->first();
        if ($duplicate) {
            throw new RuntimeException('Active applications already contain a duplicate application number within a school year. Review those records before applying this migration. No records were merged or removed.');
        }
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('active_external_app_no', 30)->nullable()->virtualAs('CASE WHEN archived_at IS NULL THEN external_app_no ELSE NULL END');
            $table->unique(['school_year', 'active_external_app_no'], 'applicants_active_import_identity_unique');
            $table->index(['external_app_no', 'school_year'], 'applicants_import_lookup_index');
            $table->index(['school_year', 'archived_at', 'applying_for_scholarship', 'created_at', 'id'], 'applicants_scholar_page_index');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'audit_logs_date_page_index');
            $table->index(['module', 'created_at'], 'audit_logs_module_date_index');
            $table->index(['action', 'created_at'], 'audit_logs_action_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_date_page_index');
            $table->dropIndex('audit_logs_module_date_index');
            $table->dropIndex('audit_logs_action_date_index');
        });
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropUnique('applicants_active_import_identity_unique');
            $table->dropIndex('applicants_import_lookup_index');
            $table->dropIndex('applicants_scholar_page_index');
            $table->dropColumn('active_external_app_no');
        });
    }
};
