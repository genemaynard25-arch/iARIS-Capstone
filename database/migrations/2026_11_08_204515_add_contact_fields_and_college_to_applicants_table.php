<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->dateTime('exam_scheduled_at')->nullable();
            $table->string('college')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['email', 'contact_number', 'address', 'date_of_birth', 'exam_scheduled_at', 'college']);
        });
    }
};