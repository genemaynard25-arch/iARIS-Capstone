<?php

namespace App\Support;

use App\Models\Applicant;

/** Anonymous daily totals for the Analytics frontend; no applicant identities leave this adapter. */
class AnalyticsFilterData
{
    public static function make(): array
    {
        $paidExam = ApplicantRecords::paidFees('application_fee');
        $reserved = ApplicantRecords::paidFees('reservation_fee');
        $groups = [];
        Applicant::whereNull('archived_at')->select([
            'id', 'school_year', 'level', 'created_at', 'address', 'gender',
            'admission_test_status', 'application_status', 'applying_for_scholarship',
            'pwd', 'ssen', 'noa_issued',
        ])->orderBy('id')->chunkById(500, function ($applicants) use (&$groups, $paidExam, $reserved) {
            foreach ($applicants as $a) {
                $dimensions = [
                    'year' => $a->school_year,
                    'department' => AdmissionStats::LEVEL_LABELS[$a->level] ?? $a->level,
                    'date' => $a->created_at?->format('Y-m-d'),
                    'location' => AddressClassifier::classify($a->address),
                ];
                $key = json_encode($dimensions);
                $groups[$key] ??= $dimensions + array_fill_keys([
                    'applicants', 'male', 'female', 'scholars', 'pwd', 'ssen', 'noaIssued',
                    'paidExam', 'reserved', 'took', 'passed', 'enrolled',
                ], 0);
                $counts = [
                    'applicants' => true, 'male' => $a->gender === 'male', 'female' => $a->gender === 'female',
                    'scholars' => $a->applying_for_scholarship, 'pwd' => $a->pwd, 'ssen' => $a->ssen,
                    'noaIssued' => $a->noa_issued, 'paidExam' => isset($paidExam[$a->id]),
                    'reserved' => isset($reserved[$a->id]),
                    'took' => in_array($a->admission_test_status, ['took_test', 'passed', 'failed', 'qualified_other_degree', 'reconsidered', 'waitlisted']),
                    'passed' => in_array($a->admission_test_status, ['passed', 'qualified_other_degree', 'reconsidered']),
                    'enrolled' => $a->application_status === 'completed',
                ];
                foreach ($counts as $metric => $value) $groups[$key][$metric] += (int) (bool) $value;
            }
        });

        return ['rows' => array_values($groups), 'years' => AdmissionStats::schoolYears(), 'today' => now('Asia/Manila')->format('Y-m-d')];
    }
}
