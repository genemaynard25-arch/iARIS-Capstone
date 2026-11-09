<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Finalizing the schema ahead of building the feature (same reasoning as the messages
// tables added earlier): Review Queue (ReviewQueueController / SubmissionsController)
// is entirely front-end placeholder data right now - this gives it somewhere real to
// write to later without another schema change. Column names and shape are taken
// directly from ReviewQueueController::items()'s existing placeholder rows, so the
// real implementation won't need to rename anything to fit.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_queue', function (Blueprint $table) {
            $table->id();
            $table->string('rq_number', 20)->unique(); // "RQ-0413"
            $table->enum('type', ['edit', 'import', 'lamp', 'self']);
            $table->string('title'); // "Payment recorded", "Batch import", "Scholarship type changed"...
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->string('subject')->nullable(); // applicant name snapshot, or the batch filename for imports
            $table->string('ref')->nullable(); // "APP-2026-0381" or "College Applicants"
            $table->string('unit')->nullable(); // "College · CITE"

            // Who submitted it. A real user for staff edits/imports/LAMP updates; null with
            // just a name/role for 'self' (the applicant portal has no iARIS user account).
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('submitted_by_name')->nullable();
            $table->string('submitted_by_role')->nullable();
            $table->timestamp('submitted_at')->useCurrent();

            $table->enum('status', ['waiting', 'approved', 'sent'])->default('waiting'); // 'sent' = sent back
            $table->text('note')->nullable(); // the submitter's note

            // edit / lamp / self: a list of [field, before, after]. import: summary counts,
            // a row preview, and validation errors instead - kept separate rather than
            // overloading one JSON column with two different shapes.
            $table->json('changes')->nullable();
            $table->json('import_summary')->nullable(); // {total, new, updated, errors}
            $table->json('import_sample')->nullable();
            $table->json('import_errors')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable(); // required when sending back, same as today's UI

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_queue');
    }
};
