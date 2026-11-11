<?php

namespace Tests\Feature;

use App\Models\ReviewQueueItem;
use App\Models\RolePermission;
use App\Support\ApplicantRecords;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\OfficeTestCase;

class DivisionApplicantEditTest extends OfficeTestCase
{
    public static function divisions(): array
    {
        return [
            'college' => ['college', 'college', 'CITE', 'BS Computer Science'],
            'IS' => ['is', 'is', 'Senior High', 'STEM'],
            'graduate' => ['graduate', 'graduate_school', 'Graduate Programs', 'Master of Business Administration'],
            'law' => ['law', 'law', 'College of Law', 'Juris Doctor'],
            'iPACE' => ['ipace', 'ipace', 'iPACE', 'BS Information Technology'],
            'ETEEAP' => ['eteeap', 'eteeap', 'ETEEAP', 'BS Business Administration'],
        ];
    }

    private function entry(string $scope, string $group, string $program): array
    {
        return [
            'scope' => $scope, 'last' => 'SaveTest', 'first' => 'Updated', 'mi' => 'Q',
            'gender' => 'Female', 'group' => $group, 'program' => $program,
            'status' => 'Pending', 'applied' => '2026-01-01', 'exam_result' => 'not_taken',
            'learning_modalities' => 'ONLINE LEARNING',
        ];
    }

    #[DataProvider('divisions')]
    public function test_admin_edits_survive_reload(string $scope, string $level, string $group, string $program): void
    {
        $applicant = $this->applicant(['level' => $level, 'program_or_track' => $program]);
        $this->actingAs($this->office('admin'))
            ->putJson('/applicants/'.$applicant->reference_number, $this->entry($scope, $group, $program))
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame('Updated', $applicant->fresh()->first_name);
        $this->assertSame($program, $applicant->fresh()->program_or_track);
        $row = collect(ApplicantRecords::forLevel($level))->firstWhere('id', $applicant->reference_number);
        $this->assertSame('Q', $row['mi']);
        $this->assertSame($program, $row['program_label'] ?? $row['program']);
    }

    #[DataProvider('divisions')]
    public function test_staff_edits_wait_for_approval(string $scope, string $level, string $group, string $program): void
    {
        RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => $scope], ['enabled' => true, 'can_edit' => true]);
        $applicant = $this->applicant(['level' => $level, 'program_or_track' => $program]);
        $this->actingAs($this->office('iato_staff'))
            ->putJson('/applicants/'.$applicant->reference_number, $this->entry($scope, $group, $program))
            ->assertOk()->assertJsonPath('review', true);
        $this->assertSame('Test', $applicant->fresh()->first_name);
        $item = ReviewQueueItem::where('applicant_id', $applicant->id)->latest('id')->firstOrFail();
        $this->assertSame($program, $item->pending_action['payload']['validated']['program']);
        $this->actingAs($this->office('admin'))->postJson('/review-queue/'.$item->rq_number.'/approve')->assertOk();
        $this->assertSame('Updated', $applicant->fresh()->first_name);
        $this->assertSame($program, $applicant->fresh()->program_or_track);
    }

    #[DataProvider('divisions')]
    public function test_viewer_cannot_save(string $scope, string $level, string $group, string $program): void
    {
        RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => $scope], ['enabled' => true, 'can_edit' => false]);
        $applicant = $this->applicant(['level' => $level]);
        $this->actingAs($this->office('iato_staff'))
            ->putJson('/applicants/'.$applicant->reference_number, $this->entry($scope, $group, $program))
            ->assertForbidden();
        $this->assertSame('Test', $applicant->fresh()->first_name);
    }

    #[DataProvider('divisions')]
    public function test_staff_can_submit_new_applicants_with_module_permission(string $scope, string $level, string $group, string $program): void
    {
        RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => $scope], ['enabled' => true, 'can_edit' => true]);
        if ($scope !== 'college') {
            RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => 'college'], ['enabled' => false, 'can_edit' => false]);
        }
        $this->actingAs($this->office('iato_staff'))->postJson('/applicants', $this->entry($scope, $group, $program))
            ->assertOk()->assertJsonPath('review', true);
        $item = ReviewQueueItem::where('submitted_by', auth()->id())->latest('id')->firstOrFail();
        $this->assertSame($level, $item->pending_action['payload']['level']);
    }
}
