<?php

namespace Tests\Feature;

use App\Support\ApplicantEditFlow;
use App\Support\ApplicantRecords;
use Tests\OfficeTestCase;

class AdditionalQaTest extends OfficeTestCase
{
    public function test_college_registrar_can_view_all_higher_education_records_but_is_registrar_cannot(): void
    {
        (require database_path('migrations/2026_11_11_130000_enable_registrar_higher_education_records.php'))->up();
        $registrar = $this->office('registrar');
        $registrar->update(['assigned_level' => 'college']);
        $this->actingAs($registrar);
        foreach (['graduate', 'law', 'ipace', 'eteeap'] as $module) {
            $this->assertTrue($registrar->canAccess($module));
            $this->assertFalse($registrar->canEdit($module));
            $this->get('/records/'.$module)->assertOk();
        }
        $registrar->update(['assigned_level' => 'is']);
        foreach (['college', 'graduate', 'law', 'ipace', 'eteeap'] as $module) {
            $this->assertFalse($registrar->canAccess($module));
            $this->get('/records/'.$module)->assertForbidden();
        }
    }

    public function test_college_codes_and_full_names_share_one_filter_and_dean_filters_by_program(): void
    {
        $this->applicant(['level' => 'college', 'college' => 'CITE', 'program_or_track' => 'BS Computer Science']);
        $this->applicant(['level' => 'college', 'college' => 'College of Information Technology and Engineering', 'program_or_track' => 'BS Information Technology']);
        $this->applicant(['level' => 'college', 'college' => 'CON', 'program_or_track' => 'BS Nursing']);
        $this->actingAs($this->office('admin'))->get('/records/college')->assertOk()
            ->assertSee('data-category="CITE"', false)
            ->assertDontSee('data-category="College of Information Technology and Engineering"', false);
        $this->assertSame(2, collect(ApplicantRecords::forLevel('college'))->where('college', 'CITE')->count());

        $dean = $this->office('dean');
        $dean->update(['assigned_college' => 'CITE', 'assigned_level' => 'college']);
        $this->actingAs($dean)->get('/college/applicants')->assertOk()
            ->assertSee('data-category="BS Computer Science"', false)
            ->assertSee('data-category="BS Information Technology"', false)
            ->assertDontSee('data-category="CITE"', false)
            ->assertDontSee('data-category="BS Nursing"', false);
    }

    public function test_grade_filter_uses_natural_numeric_order(): void
    {
        foreach (['Grade 1', 'Grade 10', 'Grade 2', 'Grade 12', 'Grade 9'] as $grade) {
            $this->applicant(['level' => 'is', 'sub_level' => 'jhs', 'year_level' => $grade]);
        }
        $this->actingAs($this->office('admin'))->get('/records/is')->assertOk()
            ->assertSeeInOrder(['<option>Grade 1</option>', '<option>Grade 2</option>', '<option>Grade 9</option>', '<option>Grade 10</option>', '<option>Grade 12</option>'], false);
    }

    public function test_new_applicant_review_includes_contact_and_application_details(): void
    {
        $diff = ApplicantEditFlow::diffForCreate([
            'last' => 'Applicant', 'first' => 'Test', 'gender' => 'Female', 'email' => 'test@example.test',
            'contact' => '+639171234567', 'address' => 'Lipa City', 'group' => 'CITE',
            'program' => 'BS Computer Science', 'status' => 'Pending', 'applied' => '2026-01-01',
        ]);
        $byLabel = collect($diff)->keyBy(fn ($change) => $change[0]);
        $this->assertSame('test@example.test', $byLabel['Email'][2]);
        $this->assertSame('+639171234567', $byLabel['Contact No.'][2]);
        $this->assertSame('Lipa City', $byLabel['Address'][2]);
        $this->assertSame('2026-01-01', $byLabel['Date Applied'][2]);
    }
}
