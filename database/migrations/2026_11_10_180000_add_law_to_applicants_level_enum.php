<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// College of Law applicants were never importable - the level column's enum didn't
// have 'law' as an option. This lets Import Data store real Law rows instead of the
// Records/Pipeline pages relying on LawRecordController::sample() forever.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN level ENUM('is','college','graduate_school','eteeap','law') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN level ENUM('is','college','graduate_school','eteeap') NOT NULL");
    }
};
