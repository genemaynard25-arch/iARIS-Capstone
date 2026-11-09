<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The stakeholder meeting settled this: one import feature, not two. admission_stats
// was a manually pre-tallied aggregate file (projections, gender/paid counts, etc.) —
// everything countable in it now comes from the applicants table instead
// (see App\Support\AdmissionStats), and the forward-looking projection/target numbers
// are auto-computed from year-over-year growth rather than uploaded.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('admission_stats');
    }

    public function down(): void
    {
        // Not recreated: the 64-column shape is gone from the app entirely (no model,
        // no import, no controller). Roll back to before 2026_10_08_121344 instead if
        // that table is ever needed again.
        Schema::create('admission_stats', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};
