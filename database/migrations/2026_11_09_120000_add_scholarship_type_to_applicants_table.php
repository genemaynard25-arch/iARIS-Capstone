<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Only meaningful when applicant_type = 'scholar'. Values match
            // ScholarController::TYPES exactly, so the LAMP/Scholars pages'
            // type badges and stat-card grouping work on real data.
            $table->enum('scholarship_type', [
                'DLSL Scholar', 'Academic Scholar', 'Athletic Scholar',
                'CHED Scholar', 'DOST Scholar', 'Other Government',
            ])->nullable()->after('applicant_type');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn('scholarship_type');
        });
    }
};
