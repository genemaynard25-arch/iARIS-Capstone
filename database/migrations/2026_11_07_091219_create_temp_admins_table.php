<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('temp_admins', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
        $table->string('reason')->nullable();
        $table->dateTime('starts_at');
        $table->dateTime('expires_at');
        $table->dateTime('revoked_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('temp_admins');
}
};
