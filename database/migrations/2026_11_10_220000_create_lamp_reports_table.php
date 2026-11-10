<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lamp_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('school_year', 9);
            $table->json('rows');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('generated_by_name');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('lamp_report_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lamp_report_id')->constrained('lamp_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['lamp_report_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lamp_report_reads');
        Schema::dropIfExists('lamp_reports');
    }
};
