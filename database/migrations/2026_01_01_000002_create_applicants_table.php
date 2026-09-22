<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_batch_id')->nullable()->constrained('import_batches');
            $table->string('reference_number', 30)->unique();
            $table->string('school_year', 9);
            $table->enum('level', ['is', 'college', 'graduate_school', 'eteeap']);
            $table->enum('sub_level', ['kindergarten', 'jhs', 'shs'])->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('gender', ['male', 'female']);
            $table->string('feeder_school', 150)->nullable();
            $table->string('program_or_track', 150)->nullable();
            $table->enum('applicant_type', ['regular', 'scholar'])->default('regular');
            $table->enum('admission_test_status', [
                'not_taken', 'took_test', 'passed', 'failed',
                'qualified_other_degree', 'reconsidered',
            ])->default('not_taken');
            $table->boolean('noa_issued')->default(false);
            $table->enum('application_status', [
                'pooling', 'submitted', 'completed', 'not_submitted',
            ])->default('pooling');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
