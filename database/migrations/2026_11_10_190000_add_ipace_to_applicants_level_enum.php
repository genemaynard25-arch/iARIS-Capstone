<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Same gap as 'law' before it (see 2026_11_10_180000): the classifier already
// produces 'ipace' as a level, but the column couldn't store it, so a real
// import with an iPACE section crashed instead of saving those rows.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN level ENUM('is','college','graduate_school','eteeap','law','ipace') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN level ENUM('is','college','graduate_school','eteeap','law') NOT NULL");
    }
};
