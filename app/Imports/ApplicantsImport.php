<?php

namespace App\Imports;

use App\Models\Applicant;
use App\Models\ImportBatch;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ApplicantsImport implements ToModel, WithHeadingRow
{
    public function __construct(protected ImportBatch $batch)
    {
    }

    public function model(array $row): \Illuminate\Database\Eloquent\Model|array|null
    {
        return new Applicant([
            'imported_batch_id' => $this->batch->id,
            'reference_number' => $this->generateReferenceNumber($row['school_year'], $row['level']),
            'school_year' => $row['school_year'],
            'level' => $row['level'],
            'sub_level' => $row['sub_level'] ?? null,
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'gender' => $row['gender'],
            'feeder_school' => $row['feeder_school'] ?? null,
            'program_or_track' => $row['program_or_track'] ?? null,
            'applicant_type' => $row['applicant_type'] ?? 'regular',
            'admission_test_status' => $row['admission_test_status'] ?? 'not_taken',
            'application_status' => $row['application_status'] ?? 'pooling',
        ]);
    }

    protected function generateReferenceNumber(string $schoolYear, string $level): string
    {
        $year = substr($schoolYear, 0, 4);
        $levelCode = strtoupper(substr($level, 0, 3));

        $count = Applicant::where('school_year', $schoolYear)
            ->where('level', $level)
            ->count();

        $sequence = str_pad($count + 1, 6, '0', STR_PAD_LEFT);

        return "IATO-{$year}-{$levelCode}-{$sequence}";
    }
}