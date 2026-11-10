<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds the Assistant Principal role (one each for Grade School, Junior High and Senior High) and the
// 'grade_school' sub-level they are assigned to (users.assigned_sub_level). ->change() works on MySQL and SQLite.
return new class extends Migration
{
    private const ROLES = [
        'admin', 'iato_staff', 'program_chair', 'dean',
        'president', 'vice_president', 'principal',
        'chancellor', 'vice_chancellor', 'registrar', 'lamp', 'toic', 'director',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [...self::ROLES, 'assistant_principal'])->default('iato_staff')->change();
            $table->enum('assigned_sub_level', ['kindergarten', 'grade_school', 'jhs', 'shs'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', self::ROLES)->default('iato_staff')->change();
            $table->enum('assigned_sub_level', ['kindergarten', 'jhs', 'shs'])->nullable()->change();
        });
    }
};
