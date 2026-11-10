<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExportController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => 'nullable|string|max:50', 'format' => 'required|in:XLSX,PDF,Print',
            'period' => 'required|string|max:255', 'year' => ['nullable', 'regex:/^\d{4}-\d{4}$/'],
            'title' => 'required_without:key|string|max:255', 'recipient' => 'nullable|string|max:255',
            'tables' => 'required_without:key|array|min:1|max:20',
            'tables.*.title' => 'required|string|max:255',
            'tables.*.columns' => 'required|array|min:1|max:30', 'tables.*.columns.*' => 'required|string|max:255',
            'tables.*.rows' => 'present|array|max:10000', 'tables.*.rows.*' => 'array|max:30',
            'tables.*.rows.*.*' => 'nullable|string|max:2000',
        ]);
        if (! empty($data['key'])) {
            abort_unless(in_array($data['period'], ['Current School Year', 'Previous School Year'], true), 422);
            $source = app(ReportController::class)->data($data['period'], $data['year'] ?? null);
            $report = collect($source['sections'])->pluck('reports')->flatten(1)->firstWhere('key', $data['key']);
            abort_unless($report, 404);
            $tables = [['title' => $report['title'], 'columns' => $report['columns'], 'rows' => array_map(
                fn ($row) => array_map(fn ($cell) => is_array($cell) ? $cell[0] : $cell, $row), $report['rows']
            )]];
            $title = $report['title'];
            $recipient = $report['recipient'];
            $year = $source['schoolYear'];
        } else {
            abort_unless($request->user()->canAccess('analytics'), 403);
            $tables = $data['tables'];
            $title = $data['title'];
            $recipient = $data['recipient'] ?? 'IATO (self)';
            $year = $data['year'] ?? null;
        }
        abort_if(array_sum(array_map(fn ($table) => count($table['rows']) * count($table['columns']), $tables)) > 150000, 422, 'Report is too large. Narrow the selected scope.');
        $id = DB::transaction(function () use ($request, $data, $tables, $title, $recipient, $year) {
            $id = DB::table('generated_reports')->insertGetId([
                'user_id' => $request->user()->id, 'generated_by_name' => $request->user()->name,
                'template_key' => $data['key'] ?? null, 'title' => $title, 'recipient' => $recipient,
                'period' => $data['period'], 'school_year' => $year, 'format' => $data['format'],
                'snapshot' => json_encode($tables, JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
            AuditLog::record('report', 'Reports', 'Report Generated', "Generated report {$id}: {$title}; {$data['format']}; school year {$year}. Recipient label: {$recipient}. No publication performed.");

            return $id;
        });
        $saved = DB::table('generated_reports')->where('id', $id)->first();

        return response()->json(['id' => $id, 'url' => route('reports.generated.download', $id), 'history' => self::shape($saved)]);
    }

    public function preview(Request $request, string $key): JsonResponse
    {
        $data = $request->validate(['period' => 'required|in:Current School Year,Previous School Year', 'year' => ['nullable', 'regex:/^\d{4}-\d{4}$/']]);
        $source = app(ReportController::class)->data($data['period'], $data['year'] ?? null);
        $report = collect($source['sections'])->pluck('reports')->flatten(1)->firstWhere('key', $key);
        abort_unless($report, 404);

        return response()->json(['report' => $report, 'schoolYear' => $source['schoolYear']]);
    }

    public static function history(): array
    {
        return DB::table('generated_reports')->when(! auth()->user()->isAdmin(), fn ($q) => $q->where('user_id', auth()->id()))
            ->latest('id')->limit(100)->get()->map(fn ($row) => self::shape($row))->all();
    }

    private static function shape(object $row): array
    {
        return [
            'id' => $row->id, 'report' => $row->template_key ?? '', 'name' => $row->title,
            'meta' => 'Saved snapshot'.($row->school_year ? ' · SY '.$row->school_year : ''),
            'recipient' => $row->recipient, 'period' => $row->period, 'format' => $row->format,
            'by' => $row->generated_by_name, 'at' => $row->created_at,
            'download' => route('reports.generated.download', $row->id),
        ];
    }

    public function download(Request $request, int $report)
    {
        $saved = DB::table('generated_reports')->where('id', $report)->first();
        abort_unless($saved, 404);
        abort_unless($request->user()->isAdmin() || $saved->user_id === $request->user()->id, 403);
        $tables = json_decode($saved->snapshot, true, 512, JSON_THROW_ON_ERROR);
        AuditLog::record('export', 'Reports', $request->boolean('preview') ? 'Report Viewed' : 'Report Downloaded', ($request->boolean('preview') ? "Viewed report {$report}" : "Downloaded report {$report}").": {$saved->title}; {$saved->format}.");
        $filename = Str::slug($saved->title).'-'.($saved->school_year ?? 'custom');
        if ($request->boolean('preview')) {
            return view('reports.print', ['saved' => $saved, 'tables' => $tables, 'print' => false]);
        }
        if ($saved->format === 'Print') {
            return view('reports.print', ['saved' => $saved, 'tables' => $tables, 'print' => true]);
        }
        if ($saved->format === 'PDF') {
            return Pdf::loadView('reports.print', ['saved' => $saved, 'tables' => $tables, 'print' => false])->setPaper('a4', 'landscape')->download($filename.'.pdf');
        }

        return response()->streamDownload(function () use ($saved, $tables) {
            $book = new Spreadsheet;
            $book->removeSheetByIndex(0);
            foreach ($tables as $index => $table) {
                $sheet = $book->createSheet();
                $sheet->setTitle('Report '.($index + 1));
                $rows = [[$table['title']], ['School year', $saved->school_year ?? 'Selected scope'], ['Period', $saved->period], ['Generated by', $saved->generated_by_name], ['Generated at', $saved->created_at], [], $table['columns'], ...$table['rows']];
                foreach ($rows as $r => $values) {
                    foreach ($values as $c => $value) {
                        $numeric = $r > 6 && ! preg_match('/app\.?\s*no|reference/i', $table['columns'][$c] ?? '') && preg_match('/^-?\d+(?:,\d{3})*(?:\.\d+)?$/', (string) $value);
                        $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($c + 1).($r + 1), $numeric ? (float) str_replace(',', '', $value) : (string) $value, $numeric ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                    }
                }
                $sheet->freezePane('A8');
                $end = Coordinate::stringFromColumnIndex(count($table['columns']));
                $sheet->getStyle('A7:'.$end.'7')->getFont()->setBold(true);
                $sheet->setAutoFilter('A7:'.$end.max(7, count($rows)));
                foreach (range(1, count($table['columns'])) as $column) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
                }
            }
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $filename.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
