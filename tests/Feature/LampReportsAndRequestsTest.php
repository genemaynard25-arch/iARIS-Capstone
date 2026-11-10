<?php

namespace Tests\Feature;

use App\Models\LampRequest;
use App\Support\LampOffice;
use Illuminate\Support\Facades\DB;
use Tests\OfficeTestCase;

class LampReportsAndRequestsTest extends OfficeTestCase
{
    public function test_reports_are_real_immutable_snapshots_with_per_user_read_status_and_audited_downloads(): void
    {
        $admin = $this->office('admin');
        $lamp = $this->office('lamp');
        $other = $this->office('lamp');
        $applicant = $this->applicant();
        $this->actingAs($admin)->postJson('/iato/lamp-reports', ['title' => 'Real scholarship report', 'school_year' => '2025-2026'])->assertCreated();
        $id = DB::table('lamp_reports')->latest('id')->value('id');
        $this->actingAs($lamp)->postJson("/lamp/reports/{$id}/read")->assertNotFound();
        $this->assertSame([], LampOffice::reports());
        $this->actingAs($admin)->postJson("/iato/lamp-reports/{$id}/publish")->assertOk();
        $applicant->update(['first_name' => 'Changed after publication']);
        $this->actingAs($lamp)->get('/lamp/reports')->assertOk()->assertSee('Real scholarship report');
        $this->assertSame(1, LampOffice::unreadReports());
        $this->postJson("/lamp/reports/{$id}/read")->assertOk()->assertJsonPath('rows.0.1', 'Applicant, Test');
        $this->postJson("/lamp/reports/{$id}/read")->assertOk();
        $this->assertSame(0, LampOffice::unreadReports());
        $this->assertSame(1, DB::table('lamp_report_reads')->where('lamp_report_id', $id)->count());
        $download = $this->get("/lamp/reports/{$id}/download")->assertOk()->assertDownload("lamp-report-{$id}.csv");
        $this->assertStringContainsString('Applicant, Test', $download->streamedContent());
        $this->actingAs($other);
        $this->assertSame(1, LampOffice::unreadReports());
        $this->assertDatabaseHas('audit_logs', ['title' => 'LAMP Report Downloaded', 'user_id' => $lamp->id]);
        $this->assertDatabaseHas('audit_logs', ['title' => 'LAMP Report Generated']);
        $this->assertDatabaseHas('audit_logs', ['title' => 'LAMP Report Published']);
    }

    public function test_requests_and_replies_persist_and_only_iato_can_resolve(): void
    {
        $admin = $this->office('admin');
        $lamp = $this->office('lamp');
        $applicant = $this->applicant();
        $data = ['reference' => $applicant->reference_number, 'reason' => 'Verify scholarship', 'text' => 'Please check the actual record.'];
        $this->actingAs($admin)->postJson('/iato/lamp-requests', $data)->assertCreated();
        $item = LampRequest::where('applicant_id', $applicant->id)->firstOrFail();
        $this->postJson('/iato/lamp-requests', $data)->assertConflict();
        $this->actingAs($lamp)->get('/lamp/messages')->assertOk()->assertSee('Please check the actual record.')->assertDontSee('Villareal, Pia');
        $this->postJson("/lamp/requests/{$item->id}/reply", ['text' => 'Verified by LAMP.'])->assertOk();
        $this->assertSame('replied', $item->fresh()->status);
        $this->postJson("/lamp/requests/{$item->id}/resolve")->assertForbidden();
        $this->actingAs($admin)->get('/iato/lamp-requests')->assertOk()->assertSee('Verified by LAMP.');
        $this->postJson("/lamp/requests/{$item->id}/reply", ['text' => 'Thank you.'])->assertOk();
        $this->assertSame('waiting', $item->fresh()->status);
        $this->postJson("/lamp/requests/{$item->id}/resolve")->assertOk();
        $this->assertSame('resolved', $item->fresh()->status);
        $this->postJson("/lamp/requests/{$item->id}/reply", ['text' => 'Late reply'])->assertConflict();
        $this->assertSame(3, $item->messages()->count());
        $this->assertDatabaseHas('audit_logs', ['title' => 'LAMP Request Resolved']);
    }

    public function test_report_and_request_permissions_and_invalid_applicant_references(): void
    {
        $lamp = $this->office('lamp');
        $dean = $this->office('dean');
        $admin = $this->office('admin');
        $data = ['title' => 'Report', 'school_year' => '2025-2026'];
        $this->actingAs($lamp)->postJson('/iato/lamp-reports', $data)->assertForbidden();
        $this->actingAs($dean)->get('/iato/lamp-requests')->assertForbidden();
        $this->get('/iato/lamp-reports')->assertForbidden();
        $this->actingAs($admin)->postJson('/iato/lamp-requests', ['reference' => 'MISSING', 'reason' => 'Check', 'text' => 'Check'])->assertNotFound();
        $this->postJson('/iato/lamp-reports', ['title' => 'Bad year', 'school_year' => '2025-2028'])->assertUnprocessable();
    }
}
