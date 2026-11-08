<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN level ENUM('is','college','graduate_school','eteeap','ipace','law') NOT NULL");
        DB::statement("ALTER TABLE users MODIFY COLUMN assigned_level ENUM('is','college','graduate_school','eteeap','ipace','law') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN level ENUM('is','college','graduate_school','eteeap') NOT NULL");
        DB::statement("ALTER TABLE users MODIFY COLUMN assigned_level ENUM('is','college','graduate_school','eteeap') NULL");
    }
};
