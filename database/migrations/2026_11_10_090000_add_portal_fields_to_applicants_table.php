<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Brings the applicants table up to the real admissions portal's export format
// (see the sample file the client shared): the Yes/No, gender and grade-level
// fields the import now reads directly, instead of a human pre-tallying them
// into a separate stats file.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('external_app_no', 30)->nullable()->after('reference_number');
            $table->string('permit_no', 50)->nullable()->after('external_app_no');
            $table->decimal('gwa', 4, 2)->nullable()->after('permit_no');
            $table->unsignedTinyInteger('age')->nullable()->after('gwa');
            // TYPE column on the portal (New Student/Freshman, Transferee, Returnee…) — a
            // different thing from applicant_type's regular/scholar, which the scholarship
            // Yes/No column below now owns.
            $table->string('student_type', 50)->nullable()->after('applicant_type');
            $table->boolean('applying_for_scholarship')->default(false)->after('scholarship_type');
            $table->boolean('pwd')->default(false)->after('applying_for_scholarship');
            $table->boolean('ssen')->default(false)->after('pwd');
            $table->string('school_address')->nullable()->after('feeder_school');
            $table->string('school_classification', 30)->nullable()->after('school_address');
            $table->string('learning_modalities', 50)->nullable()->after('school_classification');
            $table->string('nationality', 50)->nullable()->after('learning_modalities');
        });

        // sub_level gains 'grade_school' (Grades 1-6) as its own bucket, distinct from
        // 'kindergarten' (Nursery/Preschool) — the client flagged these as needing to
        // aggregate separately from Junior High.
        DB::statement("ALTER TABLE applicants MODIFY COLUMN sub_level ENUM('kindergarten','grade_school','jhs','shs') NULL");

        // admission_test_status gains 'waitlisted', to match the client's stated 4
        // outcomes (Pass, Fail, Waitlisted, Reconsidered).
        DB::statement("ALTER TABLE applicants MODIFY COLUMN admission_test_status ENUM('not_taken','took_test','passed','failed','qualified_other_degree','reconsidered','waitlisted') NOT NULL DEFAULT 'not_taken'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applicants MODIFY COLUMN admission_test_status ENUM('not_taken','took_test','passed','failed','qualified_other_degree','reconsidered') NOT NULL DEFAULT 'not_taken'");
        DB::statement("UPDATE applicants SET sub_level = 'kindergarten' WHERE sub_level = 'grade_school'");
        DB::statement("ALTER TABLE applicants MODIFY COLUMN sub_level ENUM('kindergarten','jhs','shs') NULL");

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn([
                'external_app_no', 'permit_no', 'gwa', 'age', 'student_type',
                'applying_for_scholarship', 'pwd', 'ssen',
                'school_address', 'school_classification', 'learning_modalities', 'nationality',
            ]);
        });
    }
};
