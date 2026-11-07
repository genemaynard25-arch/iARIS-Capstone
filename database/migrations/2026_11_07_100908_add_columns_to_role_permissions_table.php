<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->string('role');
            $table->string('module');
            $table->boolean('enabled')->default(true);
            $table->unique(['role', 'module']);
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropUnique(['role', 'module']);
            $table->dropColumn(['role', 'module', 'enabled']);
        });
    }
};