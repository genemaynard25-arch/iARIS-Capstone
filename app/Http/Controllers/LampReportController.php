<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\LampScholarRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LampReportController extends Controller
{
    private function authorizeIato(Request $request): void
    {
        abort_unless($request->user()->isAdmin() || ($request->user()->role === 'iato_staff' && ($request->user()->access_type === 'editor' && $request->user()->canAccess('reports'))), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeIato($request);

        return view('lamp.publish-reports', ['reports' => DB::table('lamp_reports')->latest('id')->get()]);
    }

    public function store(Request $request)
    {
        $this->authorizeIato($request);
        $data = $request->validate(['title' => 'required|string|max:255', 'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/']]);
        abort_unless((int) substr($data['school_year'], 5) === (int) substr($data['school_year'], 0, 4) + 1, 422, 'Invalid school year.');
        $id = DB::transaction(function () use ($request, $data) {
            $applicants = Applicant::where('school_year', $data['school_year'])->whereNull('archived_at')->where('applying_for_scholarship', true)->orderBy('id')->get();
            $checks = LampScholarRecord::whereIn('applicant_id', $applicants->pluck('id'))->get()->keyBy('applicant_id');
            $rows = $applicants->map(fn ($a) => [
                $a->reference_number, "{$a->last_name}, {$a->first_name}", $a->program_or_track ?? '',
                $a->admission_test_status, $checks->get($a->id)?->decision ?? 'wait',
                $checks->get($a->id)?->decision ? $checks->get($a->id)->iato_status : '',
            ])->all();
            $id = DB::table('lamp_reports')->insertGetId([
                ...$data, 'rows' => json_encode($rows, JSON_THROW_ON_ERROR), 'generated_by' => $request->user()->id,
                'generated_by_name' => $request->user()->name, 'created_at' => now(), 'updated_at' => now(),
            ]);
            AuditLog::record('create', 'LAMP', 'LAMP Report Generated', "Report {$id}: {$data['title']} ({$data['school_year']}), ".count($rows).' applicants.', $request->user()->roleInfo()[0]);

            return $id;
        });

        return $request->expectsJson() ? response()->json(['id' => $id], 201) : back()->with('success', 'Report generated. Publish it to send it to LAMP.');
    }

    public function publish(Request $request, int $report)
    {
        $this->authorizeIato($request);
        DB::transaction(function () use ($report, $request) {
            $row = DB::table('lamp_reports')->where('id', $report)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_if($row->published_at, 409, 'Report already published.');
            DB::table('lamp_reports')->where('id', $report)->update(['published_at' => now(), 'updated_at' => now()]);
            AuditLog::record('edit', 'LAMP', 'LAMP Report Published', "Published report {$report}: {$row->title} to LAMP.", $request->user()->roleInfo()[0]);
        });

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'Report sent to LAMP.');
    }

    private function published(Request $request, int $report): object
    {
        abort_unless($request->user()->isLamp() || $request->user()->isAdmin(), 403);
        $row = DB::table('lamp_reports')->where('id', $report)->whereNotNull('published_at')->first();
        abort_unless($row, 404);

        return $row;
    }

    public function show(Request $request, int $report)
    {
        $row = $this->published($request, $report);
        DB::table('lamp_report_reads')->upsert([
            ['lamp_report_id' => $report, 'user_id' => $request->user()->id, 'read_at' => now()],
        ], ['lamp_report_id', 'user_id'], ['read_at']);

        return response()->json(['rows' => json_decode($row->rows, true, 512, JSON_THROW_ON_ERROR)]);
    }

    public function download(Request $request, int $report)
    {
        $row = $this->published($request, $report);
        AuditLog::record('export', 'LAMP', 'LAMP Report Downloaded', "Downloaded report {$report}: {$row->title}.", $request->user()->roleInfo()[0]);

        return response()->streamDownload(function () use ($row) {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Reference', 'Applicant', 'Program', 'Exam result', 'LAMP decision', 'IATO status'], escape: '');
            foreach (json_decode($row->rows, true, 512, JSON_THROW_ON_ERROR) as $values) {
                fputcsv($stream, array_map(fn ($v) => preg_match('/^[\s]*[=+@-]/', (string) $v) ? "'".$v : $v, $values), escape: '');
            }
            fclose($stream);
        }, 'lamp-report-'.$report.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
