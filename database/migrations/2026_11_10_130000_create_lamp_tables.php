<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Finalizing the schema ahead of building the feature, same as review_queue. Two things
// are currently faked because there's no real second dataset to compare against
// (App\Support\LampRequests, and every scholar showing "Pending Check" in
// ScholarController::sample()/LampController):
//   1. lamp_scholar_records - LAMP Office's own, independent record of each scholar's
//      type and status. Comparing this against applicants.scholarship_type is what a
//      real Match / Discrepancy / Not Found cross-check would run on.
//   2. lamp_requests + lamp_request_messages - the "Ask LAMP to check" conversations
//      about mismatched records. Shape taken directly from LampRequests::sample().
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lamp_scholar_records', function (Blueprint $table) {
            $table->id();
            // Nullable + external_app_no fallback: LAMP's list can have someone IATO's
            // applicants table doesn't (yet), same reasoning as applicants.external_app_no.
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->string('external_app_no')->nullable();
            $table->string('scholarship_type')->nullable(); // matches ScholarController::TYPES keys
            $table->enum('status', ['active', 'probationary', 'cancelled'])->default('active');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('lamp_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->string('name'); // scholar name snapshot at request time
            $table->string('program')->nullable();
            $table->string('iato_value')->nullable(); // what IATO's record says (e.g. scholarship type)
            $table->string('lamp_value')->nullable(); // what LAMP's record says
            $table->string('reason')->nullable(); // "Scholarship type doesn't match", "Not in LAMP records"
            $table->enum('status', ['waiting', 'replied', 'resolved'])->default('waiting');
            $table->timestamps();
        });

        Schema::create('lamp_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lamp_request_id')->constrained('lamp_requests')->cascadeOnDelete();
            $table->enum('from', ['iato', 'lamp']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name'); // sender name snapshot ("Renegado, Randolph" or "LAMP Office")
            $table->string('role')->nullable(); // "Super Admin", "IATO Staff", "LAMP Office"
            $table->text('text');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lamp_request_messages');
        Schema::dropIfExists('lamp_requests');
        Schema::dropIfExists('lamp_scholar_records');
    }
};
