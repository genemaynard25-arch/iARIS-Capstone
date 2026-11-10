<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lamp_scholar_records', function (Blueprint $table) {
            $table->enum('decision', ['yes', 'no'])->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->enum('iato_status', ['pending', 'confirmed'])->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('lamp_scholar_records', function (Blueprint $table) {
            $table->dropForeign(['checked_by']);
            $table->dropColumn(['decision', 'checked_by', 'checked_at', 'iato_status']);
        });
    }
};
