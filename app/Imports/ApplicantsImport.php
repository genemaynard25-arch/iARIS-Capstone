<?php

namespace App\Imports;

use App\Models\Applicant;
use App\Models\ImportBatch;
use App\Support\AdmissionLevelClassifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads the admissions portal's own export format directly — one file per upload,
 * covering every level, with a section header row per department/grade/program
 * (e.g. "COLLEGE OF BUSINESS, ECONOMICS, ACCOUNTANCY, AND MANAGEMENT  (172)")
 * followed by that section's applicant rows, and the rest of the section's
 * not-yet-filled rows left blank. See the sample the client shared.
 *
 * Columns (by position, since the sheet has no single heading row Laravel Excel
 * can key on — the section header rows share row 1's structure):
 *   0 #, 1 APP. NO., 2 APP. DATE, 3 FULL NAME, 4 EMAIL, 5 MOBILE NO., 6 ADDRESS,
 *   7 PERMIT NO., 8 GWA, 9 AGE, 10 SEX, 11 PROGRAM CODE, 12 STRAND / MAJOR,
 *   13 YEAR LEVEL, 14 TYPE, 15 PAID EXAM, 16 PAID RESERVATION,
 *   17 APPLYING FOR SCHOLARSHIP, 18 SSEN, 19 TEST RESULT, 20 WITH NOA, 21 STATUS,
 *   22 SCHOOL LAST ATTENDED, 23 SCHOOL ADDRESS, 24 SCHOOL CLASSIFICATION,
 *   25 TESTING DATE, 26 TEST STATUS, 27 LEARNING MODALITIES, 28 PWD, 29 NATIONALITY
 *
 * Yes/No, SEX and the Pass/Fail/Waitlisted/Reconsidered test result are all read
 * and counted here — nothing needs pre-tallying into a separate stats file.
 * PAID EXAM -> a payments row (fee_type: application_fee); PAID RESERVATION -> a
 * payments row (fee_type: reservation_fee). No amount/OR is recorded (cashier-only,
 * out of scope) — just that it's paid.
 */
class ApplicantsImport implements ToModel, WithStartRow, WithCustomCsvSettings
{
    protected ?string $currentSection = null;

    public function __construct(protected ImportBatch $batch, protected string $schoolYear)
    {
    }

    public function startRow(): int
    {
        // Some exports have a sheet title row above the real header ("list-of-applicants
        // (96)"); others don't. Rather than assume a fixed position, start at row 1 and
        // have model() skip the real header row (and any title row) wherever it lands.
        return 1;
    }

    // Pinned explicitly: the sheet's many sparse rows (section headers, blank
    // template rows) can confuse delimiter auto-detection.
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'input_encoding' => 'UTF-8',
        ];
    }

    public function model(array $row): ?Applicant
    {
        // The real header row itself, wherever it lands (row 1 normally, row 2 if the
        // file has a leading sheet-title row above it) - not data, not a section.
        if (trim((string) ($row[0] ?? '')) === '#') {
            return null;
        }

        $fullName = trim((string) ($row[3] ?? ''));

        if ($fullName === '') {
            // Either a new section header ("COLLEGE OF…", "GRADE 7", "ETEEAP", …) or a
            // not-yet-filled template row. A header has text in column 0 that isn't
            // just the row number.
            $col0 = trim((string) ($row[0] ?? ''));
            if ($col0 !== '' && ! is_numeric($col0)) {
                $this->currentSection = $col0;
            }

            return null;
        }

        [$lastName, $firstName] = $this->splitName($fullName);
        $yearLevel = trim((string) ($row[13] ?? ''));
        $classified = AdmissionLevelClassifier::classify($this->currentSection, $yearLevel);

        $programOrTrack = trim((string) ($row[12] ?? '')) !== ''
            ? trim((string) $row[12])
            : (trim((string) ($row[11] ?? '')) ?: null);

        $applicant = new Applicant([
            'imported_batch_id' => $this->batch->id,
            'reference_number' => $this->generateReferenceNumber($this->schoolYear, $classified['level']),
            'external_app_no' => trim((string) ($row[1] ?? '')) ?: null,
            'permit_no' => trim((string) ($row[7] ?? '')) ?: null,
            'gwa' => is_numeric($row[8] ?? null) ? (float) $row[8] : null,
            'age' => is_numeric($row[9] ?? null) ? (int) $row[9] : null,
            'school_year' => $this->schoolYear,
            'level' => $classified['level'],
            'sub_level' => $classified['sub_level'],
            'year_level' => $yearLevel ?: null,
            'college' => $classified['college'],
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => $this->parseGender($row[10] ?? null),
            'feeder_school' => trim((string) ($row[22] ?? '')) ?: null,
            'school_address' => trim((string) ($row[23] ?? '')) ?: null,
            'school_classification' => trim((string) ($row[24] ?? '')) ?: null,
            'learning_modalities' => trim((string) ($row[27] ?? '')) ?: null,
            'nationality' => trim((string) ($row[29] ?? '')) ?: null,
            'program_or_track' => $programOrTrack,
            'applicant_type' => 'regular',
            'student_type' => trim((string) ($row[14] ?? '')) ?: null,
            'applying_for_scholarship' => $this->yesNo($row[17] ?? null),
            'pwd' => $this->yesNo($row[28] ?? null),
            'ssen' => $this->yesNo($row[18] ?? null),
            'admission_test_status' => $this->mapTestStatus($row[19] ?? null, $row[26] ?? null),
            'noa_issued' => $this->yesNo($row[20] ?? null),
            'application_status' => $this->mapApplicationStatus($row[21] ?? null),
            'email' => trim((string) ($row[4] ?? '')) ?: null,
            'contact_number' => trim((string) ($row[5] ?? '')) ?: null,
            'address' => trim((string) ($row[6] ?? '')) ?: null,
            'exam_scheduled_at' => $this->parseDate($row[25] ?? null),
        ]);

        $applicant->save();

        $appliedAt = $this->parseDate($row[2] ?? null);
        if ($appliedAt) {
            DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $appliedAt]);
        }

        $this->recordPayment($applicant->id, 'application_fee', $this->yesNo($row[15] ?? null));
        $this->recordPayment($applicant->id, 'reservation_fee', $this->yesNo($row[16] ?? null));

        return null; // already saved above (we need its id for the payments rows)
    }

    /** "REYES, JUAN H" -> ['REYES', 'JUAN H']. Falls back to splitting on whitespace if there's no comma. */
    protected function splitName(string $fullName): array
    {
        if (str_contains($fullName, ',')) {
            [$last, $first] = array_map('trim', explode(',', $fullName, 2));

            return [$last, $first];
        }

        $parts = preg_split('/\s+/', trim($fullName));
        $last = array_pop($parts);

        return [$last, implode(' ', $parts) ?: $last];
    }

    protected function parseGender(mixed $value): string
    {
        $v = strtoupper(trim((string) $value));

        return str_starts_with($v, 'F') ? 'female' : 'male';
    }

    /** "Yes", "Yes-", "No", blank, case-insensitive -> bool. Blank is treated as No. */
    protected function yesNo(mixed $value): bool
    {
        return strtoupper(substr(trim((string) $value), 0, 1)) === 'Y';
    }

    protected function mapTestStatus(mixed $testResult, mixed $testStatus): string
    {
        $value = strtolower(trim((string) ($testResult ?: $testStatus)));

        return match (true) {
            str_contains($value, 'waitlist') => 'waitlisted',
            str_contains($value, 'reconsider') => 'reconsidered',
            str_contains($value, 'fail') => 'failed',
            str_contains($value, 'pass') => 'passed',
            str_contains($value, 'qualified') && str_contains($value, 'other') => 'qualified_other_degree',
            str_contains($value, 'progress') || str_contains($value, 'in-process') || str_contains($value, 'in process') => 'took_test',
            default => 'not_taken',
        };
    }

    protected function mapApplicationStatus(mixed $status): string
    {
        $value = strtolower(trim((string) $status));

        return match (true) {
            str_contains($value, 'complet') || str_contains($value, 'enrolled') => 'completed',
            str_contains($value, 'not submit') => 'not_submitted',
            str_contains($value, 'process') || str_contains($value, 'submit') || str_contains($value, 'exam') => 'submitted',
            default => 'pooling',
        };
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function recordPayment(int $applicantId, string $feeType, bool $paid): void
    {
        if (! $paid) {
            return;
        }

        DB::table('payments')->updateOrInsert(
            ['applicant_id' => $applicantId, 'fee_type' => $feeType],
            ['status' => 'paid', 'paid_at' => now(), 'created_at' => now()]
        );
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
