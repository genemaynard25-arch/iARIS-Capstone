<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\CachedInsight;
use App\Models\ImportBatch;
use App\Models\LampRequest;
use App\Models\LampScholarRecord;
use App\Models\ReviewQueueItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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

    public static function duplicateSummary(ImportBatch $batch): array
    {
        $declared = self::fromText($batch->original_filename);
        $active = $batch->applicants()->whereNull('archived_at');
        $count = (clone $active)->count();
        $assigned = (clone $active)->distinct()->pluck('school_year')->all();
        $summary = ['declared' => $declared, 'count' => $count, 'assigned' => $assigned, 'matching' => 0, 'target_batches' => []];
        if (! $declared || ! $count || in_array($declared, $assigned, true)) {
            return $summary;
        }
        $pairs = self::duplicatePairs($batch->id, $declared)->get(['source.id as source_id', 'target.id as target_id', 'target.imported_batch_id']);
        $summary['matching'] = $pairs->pluck('source_id')->unique()->count();
        $summary['target_batches'] = $pairs->pluck('imported_batch_id')->filter()->unique()->sort()->values()->all();

        return $summary;
    }

    private static function duplicatePairs(int $batchId, string $year): Builder
    {
        return DB::table('applicants as source')->join('applicants as target', 'source.external_app_no', '=', 'target.external_app_no')
            ->where('source.imported_batch_id', $batchId)->whereNull('source.archived_at')
            ->where('target.school_year', $year)->whereNull('target.archived_at');
    }

    public static function warnings(): array
    {
        $warnings = [];
        foreach (ImportBatch::where('status', 'completed')->latest('id')->get() as $batch) {
            if (! self::fromText($batch->original_filename)) {
                continue;
            }
            $summary = self::duplicateSummary($batch);
            if ($summary['count'] && $summary['assigned'] !== [$summary['declared']]) {
                $warnings[] = [...$summary, 'batch' => $batch->id, 'file' => $batch->original_filename, 'assigned' => implode(', ', $summary['assigned'])];
            }
        }

        return $warnings;
    }

    public static function archiveDuplicateCopy(ImportBatch $batch, string $from, int $expectedCount): int
    {
        $year = self::fromText($batch->original_filename);
        abort_unless($year && $from !== $year, 422, 'Only a misassigned copy of a file with an explicit school year can be archived here.');

        return DB::transaction(function () use ($batch, $from, $expectedCount, $year) {
            $batch = ImportBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless($batch->status === 'completed', 409, 'Only completed imports can be checked for duplicates.');
            $sourceIds = $batch->applicants()->whereNull('archived_at')->pluck('id');
            abort_unless($sourceIds->count() === $expectedCount, 409, 'The active batch count changed. Refresh its results.');
            $pairs = self::duplicatePairs($batch->id, $year)->get(['source.id as source_id', 'target.id as target_id', 'target.imported_batch_id']);
            $grouped = $pairs->groupBy('source_id');
            abort_unless($grouped->count() === $sourceIds->count() && $grouped->every(fn ($rows) => $rows->count() === 1)
                && $pairs->pluck('target_id')->unique()->count() === $sourceIds->count(), 409,
                'Every active record must have exactly one matching application number in the declared school year. Manual review is needed.');
            $targetIds = $pairs->pluck('target_id');
            $allIds = $sourceIds->merge($targetIds)->unique()->sort()->values();
            $locked = DB::table('applicants')->whereIn('id', $allIds)->orderBy('id')->lockForUpdate()->get(['id', 'school_year', 'archived_at', 'imported_batch_id'])->keyBy('id');
            foreach ($sourceIds as $id) {
                $row = $locked->get($id);
                abort_unless($row && ! $row->archived_at && $row->school_year === $from && $row->imported_batch_id === $batch->id, 409, 'The source batch changed. Refresh its results.');
            }
            foreach ($targetIds as $id) {
                $row = $locked->get($id);
                abort_unless($row && ! $row->archived_at && $row->school_year === $year, 409, 'The correctly assigned copy changed. Refresh and review it.');
            }
            abort_if(ReviewQueueItem::whereIn('applicant_id', $allIds)->where('status', 'waiting')->lockForUpdate()->first(), 409, 'Resolve pending reviews before archiving this copy.');
            abort_if(LampScholarRecord::whereIn('applicant_id', $allIds)->lockForUpdate()->first(), 409, 'Scholarship records need manual review before duplicate copies can be archived.');
            abort_if(LampRequest::whereIn('applicant_id', $allIds)->lockForUpdate()->first(), 409, 'Applicant conversations need manual review before duplicate copies can be archived.');
            $mapping = $pairs->keyBy('source_id');
            $diff = [];
            $ignored = ['id', 'reference_number', 'school_year', 'imported_batch_id', 'created_at', 'updated_at', 'archived_at'];
            foreach ($sourceIds->chunk(200) as $chunk) {
                $chunkTargets = $chunk->map(fn ($id) => $mapping[$id]->target_id);
                $sources = Applicant::whereIn('id', $chunk)->lockForUpdate()->get();
                $targets = Applicant::whereIn('id', $chunkTargets)->lockForUpdate()->get()->keyBy('id');
                $payments = DB::table('payments')->whereIn('applicant_id', $chunk->merge($chunkTargets))->lockForUpdate()->get()->groupBy('applicant_id');
                foreach ($sources as $source) {
                    $target = $targets[$mapping[$source->id]->target_id];
                    abort_unless(Arr::except($source->attributesToArray(), $ignored) === Arr::except($target->attributesToArray(), $ignored), 409,
                        "Applicant details differ for {$source->reference_number}. Manual review is needed; no records were archived.");
                    $sourcePayments = $payments->get($source->id, collect());
                    $targetPayments = $payments->get($target->id, collect());
                    abort_if($sourcePayments->merge($targetPayments)->contains(fn ($payment) => $payment->amount !== null), 409, 'Payment amounts need manual review before archiving this copy.');
                    $flags = fn ($rows) => $rows->sortBy('fee_type')->map(fn ($payment) => [$payment->fee_type, $payment->status])->values()->all();
                    abort_unless($flags($sourcePayments) === $flags($targetPayments), 409, "Payment flags differ for {$source->reference_number}. Manual review is needed; no records were archived.");
                    $diff[] = ['field' => $source->reference_number, 'before' => "Active in {$from}", 'after' => "Archived duplicate of {$target->reference_number} in {$year}"];
                }
            }
            Applicant::whereIn('id', $sourceIds)->update(['archived_at' => now()]);
            CachedInsight::where('key', 'analytics')->delete();
            AuditLog::record('edit', 'Import Data', 'Duplicate Import Copy Archived',
                "Batch #{$batch->id}: archived {$sourceIds->count()} duplicate records from {$from}; kept the matching active records in {$year}. All applicant IDs and payment records were retained.",
                auth()->user()?->roleInfo()[0], $diff);

            return $sourceIds->count();
        }, 3);
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
