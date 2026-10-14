<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
   public function up(): void
{
    Schema::create('admission_stats', function (Blueprint $table) {
        $table->id();

        $table->string('level_program');                                  // A

        $table->unsignedInteger('projection_min')->nullable();             // B
        $table->unsignedInteger('projection_mid')->nullable();             // C
        $table->unsignedInteger('projection_max')->nullable();             // D
        $table->unsignedInteger('female_applicants')->nullable();          // E
        $table->unsignedInteger('male_applicants')->nullable();            // F

        $table->unsignedInteger('submitted_current')->nullable();          // G
        $table->unsignedInteger('submitted_previous')->nullable();         // H
        $table->decimal('submitted_pct_change', 6, 2)->nullable();         // I
        $table->unsignedInteger('pooling_current')->nullable();            // J
        $table->unsignedInteger('not_submitted_previous')->nullable();     // K
        $table->decimal('not_submitted_pct_change', 6, 2)->nullable();     // L
        $table->unsignedInteger('overall_total_current')->nullable();      // M
        $table->unsignedInteger('overall_total_previous')->nullable();     // N
        $table->decimal('overall_pct_change', 6, 2)->nullable();           // O

        $table->unsignedInteger('paid_female_applicants')->nullable();     // P
        $table->unsignedInteger('paid_male_applicants')->nullable();       // Q
        $table->unsignedInteger('waived_application_fee')->nullable();     // R
        $table->unsignedInteger('paid_application_fee_current')->nullable();  // S
        $table->unsignedInteger('paid_application_fee_previous')->nullable(); // T
        $table->decimal('paid_application_fee_pct_change', 6, 2)->nullable(); // U
        $table->decimal('online_to_paid_conversion_pct', 6, 2)->nullable();   // V
        $table->unsignedInteger('paid_application_fee_two_years_ago')->nullable(); // W
        $table->decimal('paid_application_fee_pct_change_prior', 6, 2)->nullable(); // X

        $table->unsignedInteger('paid_new_scholars')->nullable();          // Y
        $table->unsignedInteger('paid_regular_new')->nullable();           // Z

        $table->unsignedInteger('took_test_current')->nullable();          // AA
        $table->unsignedInteger('took_test_previous')->nullable();         // AB
        $table->unsignedInteger('failed_qualified_certificate')->nullable(); // AC
        $table->unsignedInteger('failed_to_recon')->nullable();            // AD
        $table->unsignedInteger('qualified_other_degree')->nullable();     // AE
        $table->unsignedInteger('qd_to_recon')->nullable();                // AF
        $table->unsignedInteger('passed_current')->nullable();             // AG
        $table->unsignedInteger('passed_previous')->nullable();            // AH
        $table->unsignedInteger('with_noa')->nullable();                  // AI

        $table->unsignedInteger('reserved_regular_current')->nullable();   // AJ
        $table->unsignedInteger('reserved_regular_previous')->nullable();  // AK
        $table->decimal('reserved_regular_pct_change', 6, 2)->nullable();  // AL
        $table->unsignedInteger('reserved_scholar_current')->nullable();   // AM
        $table->unsignedInteger('reserved_scholar_previous')->nullable();  // AN
        $table->unsignedInteger('total_reserved_current')->nullable();     // AO
        $table->unsignedInteger('total_reserved_previous')->nullable();    // AP
        $table->decimal('total_reserved_pct_change', 6, 2)->nullable();    // AQ

        $table->integer('remaining_slots_min')->nullable();                // AR
        $table->integer('remaining_slots_mid')->nullable();                // AS
        $table->integer('remaining_slots_max')->nullable();                // AT

        $table->decimal('status_vs_target_regular_min', 6, 2)->nullable(); // AU
        $table->decimal('status_vs_target_regular_mid', 6, 2)->nullable(); // AV
        $table->decimal('status_vs_target_regular_max', 6, 2)->nullable(); // AW
        $table->decimal('status_vs_target_overall_min', 6, 2)->nullable(); // AX
        $table->decimal('status_vs_target_overall_mid', 6, 2)->nullable(); // AY
        $table->decimal('status_vs_target_overall_max', 6, 2)->nullable(); // AZ

        $table->integer('remaining_slots_all_applicants')->nullable();     // BA
        $table->unsignedInteger('officially_enrolled')->nullable();        // BB

        $table->decimal('acceptance_rate_current', 6, 2)->nullable();      // BC
        $table->decimal('acceptance_rate_previous', 6, 2)->nullable();     // BD
        $table->decimal('reserved_conversion_rate_current', 6, 2)->nullable(); // BE
        $table->decimal('reserved_conversion_rate_previous', 6, 2)->nullable(); // BF
        $table->decimal('online_to_paid_rate', 6, 2)->nullable();          // BG
        $table->decimal('paid_to_test_rate', 6, 2)->nullable();            // BH
        $table->decimal('online_to_test_rate', 6, 2)->nullable();          // BI
        $table->decimal('test_to_passed_rate', 6, 2)->nullable();          // BJ
        $table->decimal('passed_to_reserved_rate', 6, 2)->nullable();      // BK
        $table->unsignedInteger('slots')->nullable();                      // BL

        $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('admission_stats');
}
};
