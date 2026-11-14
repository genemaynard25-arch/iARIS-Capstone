<?php

namespace Tests\Feature;

use App\Models\AccessRequest;
use App\Models\User;
use Tests\OfficeTestCase;

class AccountAssignmentsTest extends OfficeTestCase
{
    private function payload(array $overrides = []): array
    {
        return array_replace(['first' => 'Assignment', 'last' => 'Test',
            'email' => 'assignment-'.bin2hex(random_bytes(4)).'@dlsl.edu.ph',
            'role' => 'iato_staff', 'department' => 'IATO'], $overrides);
    }

    public function test_role_and_department_must_match(): void
    {
        $this->actingAs($this->office('admin'))->postJson('/accounts', $this->payload(['department' => 'CITE']))
            ->assertUnprocessable()->assertJsonValidationErrors('department');
    }

    public function test_dean_college_is_derived_and_conflicting_assignment_rejected(): void
    {
        $this->actingAs($this->office('admin'))->postJson('/accounts', $this->payload(['role' => 'dean', 'department' => 'CCJE']))
            ->assertOk()->assertJsonPath('user.assignedCollege', 'CCJE');
        $this->postJson('/accounts', $this->payload(['role' => 'dean', 'department' => 'CITE', 'assigned_college' => 'CON']))
            ->assertUnprocessable()->assertJsonValidationErrors('assigned_college');
    }

    public function test_college_chairs_cannot_choose_another_colleges_program(): void
    {
        $this->actingAs($this->office('admin'))->postJson('/accounts', $this->payload(['role' => 'program_chair',
            'department' => 'CITE', 'assigned_program' => 'BS Nursing']))
            ->assertUnprocessable()->assertJsonValidationErrors('assigned_program');
        $this->postJson('/accounts', $this->payload(['role' => 'program_chair', 'department' => 'CITE',
            'assigned_program' => 'BS Computer Science']))->assertOk()->assertJsonPath('user.assignedCollege', 'CITE');
    }

    public function test_graduate_programs_head_remains_a_program_chair(): void
    {
        $data = $this->payload(['role' => 'program_chair', 'department' => 'Graduate Programs']);
        $this->actingAs($this->office('admin'))->postJson('/accounts', $data)->assertOk();
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertTrue($user->isGradChair());
        $this->assertNull($user->assigned_college);
        $this->assertNull($user->assigned_program);
    }

    public function test_assistant_principal_requires_a_valid_sub_level(): void
    {
        $data = $this->payload(['role' => 'assistant_principal', 'department' => 'Integrated School']);
        $this->actingAs($this->office('admin'))->postJson('/accounts', $data)
            ->assertUnprocessable()->assertJsonValidationErrors('assigned_sub_level');
        $this->postJson('/accounts', $data + ['assigned_sub_level' => 'grade_school'])->assertOk();
        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('is', $user->assigned_level);
        $this->assertSame('Grade School', $user->assistantLevel());
    }

    public function test_role_change_clears_old_assignments(): void
    {
        $user = $this->office('program_chair');
        $user->update(['department' => 'Graduate Programs', 'assigned_level' => 'graduate_school']);
        $this->actingAs($this->office('admin'))->putJson('/accounts/'.$user->id, $this->payload(['status' => 'Active']))->assertOk();
        $this->assertNull($user->fresh()->assigned_level);
        $this->assertNull($user->fresh()->assigned_sub_level);
        $this->assertNull($user->fresh()->assigned_program);
    }

    public function test_editing_legacy_shs_principal_does_not_broaden_access(): void
    {
        $user = $this->office('principal');
        $user->update(['assigned_level' => 'is', 'assigned_sub_level' => 'shs']);
        $this->actingAs($this->office('admin'))->putJson('/accounts/'.$user->id,
            $this->payload(['role' => 'principal', 'department' => 'Integrated School', 'status' => 'Active']))->assertOk();
        $this->assertSame('shs', $user->fresh()->assigned_sub_level);
    }

    public function test_access_request_approval_uses_the_same_assignment_rules(): void
    {
        $request = AccessRequest::create(['first_name' => 'Request', 'last_name' => 'Chair',
            'email' => 'requested-chair@example.test', 'password' => bcrypt('password'),
            'position' => 'Program Chair', 'department' => 'Graduate Programs', 'reason' => 'Testing']);
        $this->actingAs($this->office('admin'))->postJson('/access-requests/'.$request->id.'/approve',
            ['role' => 'program_chair', 'department' => 'IATO'])->assertUnprocessable();
        $this->assertSame('waiting', $request->fresh()->status);
        $this->postJson('/access-requests/'.$request->id.'/approve',
            ['role' => 'program_chair', 'department' => 'Graduate Programs'])->assertOk();
        $this->assertTrue(User::where('email', $request->email)->firstOrFail()->isGradChair());
    }

    public function test_existing_invalid_assignments_are_flagged_without_being_rewritten(): void
    {
        $user = $this->office('iato_staff');
        $user->update(['department' => 'CITE']);
        $response = $this->actingAs($this->office('admin'))->get('/accounts')->assertOk();
        $row = collect($response->viewData('users'))->firstWhere('id', $user->id);
        $this->assertTrue($row['assignmentWarning']);
        $this->assertSame('CITE', $user->fresh()->department);
    }
}
