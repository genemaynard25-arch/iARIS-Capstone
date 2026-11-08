<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds the Director role (the iPACE Director, who also heads ETEEAP). ->change() works on MySQL and SQLite.
return new class extends Migration
{
    private const ROLES = [
        'admin', 'iato_staff', 'program_chair', 'dean',
        'president', 'vice_president', 'principal',
        'chancellor', 'vice_chancellor', 'registrar', 'lamp', 'toic',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [...self::ROLES, 'director'])->default('iato_staff')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', self::ROLES)->default('iato_staff')->change();
        });
    }
};
