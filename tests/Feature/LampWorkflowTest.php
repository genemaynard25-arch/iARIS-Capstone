<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\LampScholarRecord;
use App\Models\TempAdmin;
use Tests\OfficeTestCase;

class LampWorkflowTest extends OfficeTestCase
{
    public function test_submission_approval_and_separate_iato_confirmation_are_persistent_and_audited(): void
    {
        $lamp = $this->office('lamp');
        $admin = $this->office('admin');
        $staff = $this->office('iato_staff');
        $applicant = $this->applicant();
        $item = $this->submit($lamp, $applicant);
        $this->assertDatabaseMissing('lamp_scholar_records', ['applicant_id' => $applicant->id]);
        $this->actingAs($lamp)->postJson("/lamp/checks/{$applicant->id}", ['action' => 'no'])->assertConflict();
        $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/approve")->assertOk();
        $this->assertDatabaseHas('lamp_scholar_records', ['applicant_id' => $applicant->id, 'decision' => 'yes', 'iato_status' => 'pending', 'checked_by' => $lamp->id]);
        $this->actingAs($lamp)->get('/lamp/scholars')->assertOk()->assertSee($applicant->reference_number);
        $this->actingAs($staff)->postJson("/iato/lamp-checks/{$applicant->id}/confirm")->assertOk();
        $this->assertDatabaseHas('lamp_scholar_records', ['applicant_id' => $applicant->id, 'iato_status' => 'confirmed', 'confirmed_by' => $staff->id]);
        $this->actingAs($lamp)->postJson("/lamp/checks/{$applicant->id}", ['action' => 'undo'])->assertUnprocessable();
        $this->assertSame(3, AuditLog::where('details', 'like', '%'.$item->rq_number.'%')->count());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $lamp->id, 'title' => 'LAMP Check Submitted for Review', 'ip_address' => '127.0.0.1']);
    }

    public function test_send_back_leaves_the_decision_untouched_and_undo_needs_approval(): void
    {
        $lamp = $this->office('lamp');
        $admin = $this->office('admin');
        $applicant = $this->applicant();
        $record = LampScholarRecord::create(['applicant_id' => $applicant->id, 'decision' => 'no']);
        $item = $this->submit($lamp, $applicant, 'yes');
        $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/send-back", ['reason' => 'Check documents'])->assertOk();
        $this->assertSame('no', $record->fresh()->decision);
        $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/approve")->assertConflict();
        $item = $this->submit($lamp, $applicant, 'undo');
        $this->assertSame('no', $record->fresh()->decision);
        $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/approve")->assertOk();
        $this->assertNull($record->fresh()->decision);
        $this->assertDatabaseHas('audit_logs', ['title' => 'Review Queue Item Sent Back']);
    }

    public function test_stale_ineligible_and_already_decided_reviews_are_rejected(): void
    {
        $lamp = $this->office('lamp');
        $admin = $this->office('admin');
        $applicant = $this->applicant();
        $item = $this->submit($lamp, $applicant);
        $record = LampScholarRecord::create(['applicant_id' => $applicant->id, 'decision' => 'no']);
        $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/approve")->assertConflict();
        $this->assertSame('waiting', $item->fresh()->status);
        $record->update(['decision' => null]);
        $applicant->update(['archived_at' => now()]);
        $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/approve")->assertUnprocessable();
        $this->assertSame('waiting', $item->fresh()->status);
    }

    public function test_iato_cannot_confirm_while_an_action_is_pending(): void
    {
        $lamp = $this->office('lamp');
        $admin = $this->office('admin');
        $applicant = $this->applicant();
        LampScholarRecord::create(['applicant_id' => $applicant->id, 'decision' => 'yes']);
        $this->submit($lamp, $applicant, 'undo');
        $this->actingAs($admin)->postJson("/iato/lamp-checks/{$applicant->id}/confirm")->assertConflict();
    }

    public function test_other_roles_cannot_submit_approve_or_confirm(): void
    {
        $lamp = $this->office('lamp');
        $applicant = $this->applicant();
        $item = $this->submit($lamp, $applicant);
        foreach (['dean', 'registrar', 'iato_staff', 'director', 'lamp'] as $role) {
            $user = $this->office($role);
            $this->actingAs($user)->postJson("/review-queue/{$item->rq_number}/approve")->assertForbidden();
            $this->actingAs($user)->postJson("/review-queue/{$item->rq_number}/send-back", ['reason' => 'No'])->assertForbidden();
            if ($role !== 'lamp') {
                $this->postJson("/lamp/checks/{$applicant->id}", ['action' => 'yes'])->assertForbidden();
            }
            if ($role !== 'iato_staff') {
                $this->postJson("/iato/lamp-checks/{$applicant->id}/confirm")->assertForbidden();
            }
        }
    }

    public function test_temporary_admin_can_review_and_repeated_approval_is_rejected(): void
    {
        $lamp = $this->office('lamp');
        $admin = $this->office('admin');
        $staff = $this->office('iato_staff');
        $applicant = $this->applicant();
        TempAdmin::create(['user_id' => $staff->id, 'granted_by' => $admin->id, 'starts_at' => now()->subMinute(), 'expires_at' => now()->addHour()]);
        $item = $this->submit($lamp, $applicant, 'no');
        $this->actingAs($staff)->postJson("/review-queue/{$item->rq_number}/approve")->assertOk();
        $this->postJson("/review-queue/{$item->rq_number}/approve")->assertConflict();
        $this->assertDatabaseHas('lamp_scholar_records', ['applicant_id' => $applicant->id, 'decision' => 'no']);
    }

    public function test_audit_failure_rolls_back_approval_and_submission(): void
    {
        $lamp = $this->office('lamp');
        $admin = $this->office('admin');
        $applicant = $this->applicant();
        $item = $this->submit($lamp, $applicant);
        AuditLog::creating(function () {
            throw new \RuntimeException('Audit unavailable');
        });
        try {
            $this->actingAs($admin)->postJson("/review-queue/{$item->rq_number}/approve")->assertStatus(500);
            $this->assertSame('waiting', $item->fresh()->status);
            $this->assertDatabaseMissing('lamp_scholar_records', ['applicant_id' => $applicant->id]);
        } finally {
            AuditLog::flushEventListeners();
            AuditLog::clearBootedModels();
        }
    }

    public function test_audit_actor_snapshots_survive_role_changes_and_entries_cannot_be_edited(): void
    {
        $lamp = $this->office('lamp');
        $this->submit($lamp, $this->applicant());
        $log = AuditLog::latest('id')->firstOrFail();
        $lamp->update(['role' => 'dean', 'name' => 'New name']);
        $this->assertSame('LAMP Office', $log->fresh()->actor_role);
        $this->assertNotSame('New name', $log->fresh()->actor_name);
        $this->expectException(\LogicException::class);
        $log->update(['title' => 'Changed']);
    }
}
