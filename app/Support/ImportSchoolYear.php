<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\CachedInsight;
use App\Models\ImportBatch;
use App\Models\ReviewQueueItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportSchoolYear
{
    public static function fromText(string $text): ?string
    {
        if (! preg_match('/(?<![A-Za-z0-9])(?:SY|AY|SCHOOL[ _]YEAR)[\s_:–-]*(\d{4})\s*[-_–]\s*(\d{4})/iu', $text, $match)) {
            return null;
        }

        return (int) $match[2] === (int) $match[1] + 1 ? $match[1].'-'.$match[2] : null;
    }

    public static function declaredYear(UploadedFile $file): ?string
    {
        $years = array_filter([self::fromText($file->getClientOriginalName())]);
        if (strtolower($file->getClientOriginalExtension()) === 'xlsx') {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new class implements IReadFilter
            {
                public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                {
                    return $row <= 12 && in_array($columnAddress, ['A', 'B'], true);
                }
            });
            $book = $reader->load($file->getRealPath());
            try {
                foreach ($book->getAllSheets() as $sheet) {
                    $years[] = self::fromText((string) $sheet->getCell('A1')->getValue());
                    for ($row = 1; $row <= min(12, $sheet->getHighestRow()); $row++) {
                        $label = (string) $sheet->getCell('A'.$row)->getValue();
                        if (preg_match('/^school[ _]year\b/i', $label)) {
                            $years[] = self::fromText('SY '.(string) $sheet->getCell('B'.$row)->getValue());
                        }
                    }
                }
            } finally {
                $book->disconnectWorksheets();
            }
        }
        $years = array_values(array_unique(array_filter($years)));
        if (count($years) > 1) {
            throw ValidationException::withMessages(['school_year' => 'The file contains conflicting school-year declarations. Correct its filename or workbook metadata before importing.']);
        }

        return $years[0] ?? null;
    }

    public static function warnings(): array
    {
        $warnings = [];
        foreach (ImportBatch::withCount('applicants')->where('status', 'completed')->latest('id')->get() as $batch) {
            $declared = self::fromText($batch->original_filename);
            if (! $declared || ! $batch->applicants_count) {
                continue;
            }
            $assigned = $batch->applicants()->distinct()->pluck('school_year')->all();
            if ($assigned !== [$declared]) {
                $warnings[] = ['batch' => $batch->id, 'file' => $batch->original_filename, 'declared' => $declared, 'assigned' => implode(', ', $assigned), 'count' => $batch->applicants_count];
            }
        }

        return $warnings;
    }

    public static function correct(ImportBatch $batch, string $from, string $to, int $expectedCount): int
    {
        abort_if($from === $to, 422, 'Choose a different target school year.');

        return DB::transaction(function () use ($batch, $from, $to, $expectedCount) {
            $batch = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless($batch->status === 'completed', 409, 'Only completed imports can be corrected.');
            $rows = Applicant::where('imported_batch_id', $batch->id)->orderBy('id')->lockForUpdate()->get(['id', 'school_year', 'external_app_no']);
            abort_unless($rows->count() === $expectedCount && $rows->every(fn ($row) => $row->school_year === $from), 409, 'The batch changed. Refresh its results before correcting the school year.');
            abort_if(ReviewQueueItem::whereIn('applicant_id', $rows->pluck('id'))->where('status', 'waiting')->exists(), 409, 'Resolve pending applicant reviews before correcting this batch.');
            $duplicates = DB::table('applicants as target')->join('applicants as source', 'source.external_app_no', '=', 'target.external_app_no')
                ->where('source.imported_batch_id', $batch->id)->where('target.school_year', $to)->exists();
            abort_if($duplicates, 409, 'The target school year already contains matching application numbers. No records were moved.');
            Applicant::whereIn('id', $rows->pluck('id'))->update(['school_year' => $to]);
            CachedInsight::where('key', 'analytics')->delete();
            AuditLog::record('edit', 'Import Data', 'Import School Year Corrected',
                "Batch #{$batch->id} ({$batch->original_filename}): moved {$rows->count()} applicants from {$from} to {$to}; applicant IDs, references and payment records preserved.",
                auth()->user()?->roleInfo()[0], [['field' => 'school_year', 'before' => $from, 'after' => $to], ['field' => 'affected applicants', 'before' => $rows->count(), 'after' => $rows->count()]]);

            return $rows->count();
        });
    }
}
