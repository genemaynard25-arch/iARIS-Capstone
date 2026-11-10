<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 'changes' is display-only ([label, before, after] triples the Blade/JS already reads by
// index). Applying an approved edit needs the raw action + payload, not the human labels.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_queue', function (Blueprint $table) {
            $table->json('pending_action')->nullable()->after('changes');
        });
    }

    public function down(): void
    {
        Schema::table('review_queue', function (Blueprint $table) {
            $table->dropColumn('pending_action');
        });
    }
};
