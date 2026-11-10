<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lamp_scholar_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->unique()->constrained('applicants')->cascadeOnDelete();
            $table->enum('decision', ['yes', 'no']);
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamp('checked_at');
            $table->enum('iato_status', ['pending', 'confirmed'])->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lamp_scholar_records');
    }
};
