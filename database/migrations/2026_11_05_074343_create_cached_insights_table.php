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
    Schema::create('cached_insights', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique(); // e.g. 'analytics' — lets other pages reuse this table later
        $table->json('payload');
        $table->timestamp('generated_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('cached_insights');
}
};