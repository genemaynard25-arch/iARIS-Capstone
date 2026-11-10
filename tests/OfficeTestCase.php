<?php

namespace Tests;

use App\Models\Applicant;
use App\Models\ReviewQueueItem;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

abstract class OfficeTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('These integration tests require a migrated, isolated MySQL/MariaDB test database. See docs/stabilization-verification.md.');
        }
    }

    protected function office(string $role): User
    {
        if ($role === 'iato_staff') {
            RolePermission::updateOrCreate(['role' => $role, 'module' => 'edit'], ['enabled' => true]);
        }
        $user = User::factory()->create(['role' => $role, 'status' => 'Active', 'access_type' => 'editor']);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user;
    }

    protected function applicant(array $overrides = []): Applicant
    {
        return Applicant::create(array_replace([
            'reference_number' => 'TEST-'.bin2hex(random_bytes(6)), 'school_year' => '2025-2026',
            'level' => 'ipace', 'first_name' => 'Test', 'last_name' => 'Applicant', 'gender' => 'female',
            'applying_for_scholarship' => true, 'admission_test_status' => 'passed', 'program_or_track' => 'Test Program',
        ], $overrides));
    }

    protected function submit(User $lamp, Applicant $applicant, string $action = 'yes'): ReviewQueueItem
    {
        $this->actingAs($lamp)->postJson("/lamp/checks/{$applicant->id}", ['action' => $action])->assertOk()->assertJsonPath('pending_review', true);

        return ReviewQueueItem::where('applicant_id', $applicant->id)->latest('id')->firstOrFail();
    }
}
