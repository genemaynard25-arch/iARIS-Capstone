<?php

namespace App\Imports;

use App\Models\AdmissionStat;
use App\Models\ImportBatch;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AdmissionStatsImport implements ToModel, WithStartRow, SkipsEmptyRows
{
    public function __construct(protected ImportBatch $batch)
    {
    }

    public function startRow(): int
    {
        return 3;
    }

    public function model(array $row): ?AdmissionStat
    {
        if (blank($row[0] ?? null)) {
            return null;
        }

        return new AdmissionStat([
            'import_batch_id' => $this->batch->id,

            'level_program' => $row[0],                              // A

            'projection_min' => $this->toInt($row[1]),                // B
            'projection_mid' => $this->toInt($row[2]),                // C
            'projection_max' => $this->toInt($row[3]),                // D
            'female_applicants' => $this->toInt($row[4]),             // E
            'male_applicants' => $this->toInt($row[5]),               // F

            'submitted_current' => $this->toInt($row[6]),             // G
            'submitted_previous' => $this->toInt($row[7]),            // H
            'submitted_pct_change' => $this->toPercent($row[8]),      // I
            'pooling_current' => $this->toInt($row[9]),               // J
            'not_submitted_previous' => $this->toInt($row[10]),       // K
            'not_submitted_pct_change' => $this->toPercent($row[11]), // L
            'overall_total_current' => $this->toInt($row[12]),        // M
            'overall_total_previous' => $this->toInt($row[13]),       // N
            'overall_pct_change' => $this->toPercent($row[14]),       // O

            'paid_female_applicants' => $this->toInt($row[15]),       // P
            'paid_male_applicants' => $this->toInt($row[16]),         // Q
            'waived_application_fee' => $this->toInt($row[17]),       // R
            'paid_application_fee_current' => $this->toInt($row[18]), // S
            'paid_application_fee_previous' => $this->toInt($row[19]), // T
            'paid_application_fee_pct_change' => $this->toPercent($row[20]), // U
            'online_to_paid_conversion_pct' => $this->toPercent($row[21]),   // V
            'paid_application_fee_two_years_ago' => $this->toInt($row[22]), // W
            'paid_application_fee_pct_change_prior' => $this->toPercent($row[23]), // X

            'paid_new_scholars' => $this->toInt($row[24]),            // Y
            'paid_regular_new' => $this->toInt($row[25]),             // Z

            'took_test_current' => $this->toInt($row[26]),            // AA
            'took_test_previous' => $this->toInt($row[27]),           // AB
            'failed_qualified_certificate' => $this->toInt($row[28]), // AC
            'failed_to_recon' => $this->toInt($row[29]),              // AD
            'qualified_other_degree' => $this->toInt($row[30]),       // AE
            'qd_to_recon' => $this->toInt($row[31]),                  // AF
            'passed_current' => $this->toInt($row[32]),               // AG
            'passed_previous' => $this->toInt($row[33]),              // AH
            'with_noa' => $this->toInt($row[34]),                     // AI

            'reserved_regular_current' => $this->toInt($row[35]),     // AJ
            'reserved_regular_previous' => $this->toInt($row[36]),    // AK
            'reserved_regular_pct_change' => $this->toPercent($row[37]), // AL
            'reserved_scholar_current' => $this->toInt($row[38]),     // AM
            'reserved_scholar_previous' => $this->toInt($row[39]),    // AN
            'total_reserved_current' => $this->toInt($row[40]),       // AO
            'total_reserved_previous' => $this->toInt($row[41]),      // AP
            'total_reserved_pct_change' => $this->toPercent($row[42]), // AQ

            'remaining_slots_min' => $this->toInt($row[43]),          // AR
            'remaining_slots_mid' => $this->toInt($row[44]),          // AS
            'remaining_slots_max' => $this->toInt($row[45]),          // AT

            'status_vs_target_regular_min' => $this->toPercent($row[46]), // AU
            'status_vs_target_regular_mid' => $this->toPercent($row[47]), // AV
            'status_vs_target_regular_max' => $this->toPercent($row[48]), // AW
            'status_vs_target_overall_min' => $this->toPercent($row[49]), // AX
            'status_vs_target_overall_mid' => $this->toPercent($row[50]), // AY
            'status_vs_target_overall_max' => $this->toPercent($row[51]), // AZ

            'remaining_slots_all_applicants' => $this->toInt($row[52]), // BA
            'officially_enrolled' => $this->toInt($row[53]),           // BB

            'acceptance_rate_current' => $this->toPercent($row[54]),  // BC
            'acceptance_rate_previous' => $this->toPercent($row[55]), // BD
            'reserved_conversion_rate_current' => $this->toPercent($row[56]), // BE
            'reserved_conversion_rate_previous' => $this->toPercent($row[57]), // BF
            'online_to_paid_rate' => $this->toPercent($row[58]),      // BG
            'paid_to_test_rate' => $this->toPercent($row[59]),        // BH
            'online_to_test_rate' => $this->toPercent($row[60]),      // BI
            'test_to_passed_rate' => $this->toPercent($row[61]),      // BJ
            'passed_to_reserved_rate' => $this->toPercent($row[62]),  // BK
            'slots' => $this->toInt($row[63]),                        // BL
        ]);
    }

    protected function toInt(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function toPercent(mixed $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        return (float) str_replace('%', '', (string) $value);
    }
}