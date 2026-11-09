<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Laravel's 'encrypted' cast produces ciphertext roughly 5-6x longer than the plaintext
// (a 4-character name encrypts to ~200 characters) - these columns need to be wide enough
// to hold it, and gender can't stay a strict ENUM at all once encrypted.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN first_name TEXT NOT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN last_name TEXT NOT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN gender VARCHAR(255) NOT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN feeder_school TEXT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN email TEXT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN contact_number TEXT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN address TEXT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN first_name VARCHAR(100) NOT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN last_name VARCHAR(100) NOT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN gender ENUM('male','female') NOT NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN feeder_school VARCHAR(150) NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN email VARCHAR(255) NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN contact_number VARCHAR(255) NULL");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN address VARCHAR(255) NULL");
    }
};
