<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The portal's YEAR LEVEL column (e.g. "Grade 7", "1st Year", "Nursery") was only ever
// read transiently to classify level/sub_level - the literal text was never stored, so
// the Records pages' Grade/Year Level columns had nothing to show and stayed hardcoded
// to "-". This keeps the raw value so those columns can display it for real.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('year_level', 50)->nullable()->after('sub_level');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn('year_level');
        });
    }
};
