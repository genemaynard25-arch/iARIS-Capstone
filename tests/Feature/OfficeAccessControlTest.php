<?php

namespace Tests\Feature;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\OfficeTestCase;

class OfficeAccessControlTest extends OfficeTestCase
{
    public static function officeModules(): array
    {
        $cases = [];
        foreach (['dean', 'principal', 'assistant_principal'] as $role) {
            foreach (['dashboard', 'applicants', 'analytics', 'reports'] as $module) {
                $cases[$role.' '.$module] = [$role, $module];
            }
        }

        return $cases;
    }

    private function scopedOffice(string $role): User
    {
        foreach (['dashboard', 'applicants', 'analytics', 'reports', 'college', 'is'] as $module) {
            RolePermission::updateOrCreate(['role' => $role, 'module' => $module], ['enabled' => true]);
        }
        $user = $this->office($role);
        $user->update([
            'assigned_college' => $role === 'dean' ? 'CITE' : null,
            'assigned_level' => $role === 'dean' ? 'college' : 'is',
            'assigned_sub_level' => $role === 'assistant_principal' ? 'jhs' : null,
        ]);

        return $user;
    }

    private function sidebarLinks(string $html): array
    {
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $links = (new \DOMXPath($document))->query('//aside[@id="sidebar"]//a/@href');

        return array_map(fn ($link) => $link->nodeValue, iterator_to_array($links));
    }

    #[DataProvider('officeModules')]
    public function test_access_control_changes_affect_existing_office_accounts_and_navigation(string $role, string $module): void
    {
        $user = $this->scopedOffice($role);
        $base = $role === 'dean' ? '/college' : '/principal';
        $path = $base.($module === 'dashboard' ? '' : '/'.$module);
        $this->actingAs($user)->get($path)->assertOk();
        $admin = $this->office('admin');
        $this->actingAs($admin)->postJson('/access-control/permissions', [
            'changes' => [['role' => $role, 'module' => $module, 'enabled' => false]],
        ])->assertOk();

        $this->actingAs($user)->get($path)->assertForbidden();
        $response = $this->get($base.'/messages')->assertOk();
        $this->assertNotContains(url($path), $this->sidebarLinks($response->getContent()));
        if ($module === 'applicants') {
            $this->get('/records/'.($role === 'dean' ? 'college' : 'is'))->assertForbidden();
        }
        if ($module === 'reports') {
            $this->getJson('/office-reports/preview/programs')->assertForbidden();
            $this->postJson('/office-reports', ['key' => 'programs', 'format' => 'XLSX'])->assertForbidden();
            $this->get('/office-reports/999999')->assertForbidden();
        }

        $this->actingAs($admin)->postJson('/access-control/permissions', [
            'changes' => [['role' => $role, 'module' => $module, 'enabled' => true]],
        ])->assertOk();
        $this->actingAs($user)->get($path)->assertOk();
        $response = $this->get($base.'/messages')->assertOk();
        $this->assertContains(url($path), $this->sidebarLinks($response->getContent()));
    }

    public function test_revoking_reports_also_blocks_an_existing_saved_download(): void
    {
        $dean = $this->scopedOffice('dean');
        $this->actingAs($dean)->postJson('/office-reports', ['key' => 'programs', 'format' => 'CSV'])->assertOk();
        $reportId = DB::table('generated_reports')->where('user_id', $dean->id)->latest('id')->value('id');
        $this->get('/office-reports/'.$reportId)->assertOk();
        RolePermission::where('role', 'dean')->where('module', 'reports')->update(['enabled' => false]);
        $this->get('/office-reports/'.$reportId)->assertForbidden();
    }

    public function test_admin_can_still_preview_office_pages_with_role_permissions_disabled(): void
    {
        foreach (['dean', 'principal'] as $role) {
            foreach (['dashboard', 'applicants', 'analytics', 'reports'] as $module) {
                RolePermission::updateOrCreate(['role' => $role, 'module' => $module], ['enabled' => false]);
            }
        }
        $this->actingAs($this->office('admin'));
        foreach (['/college', '/principal'] as $base) {
            foreach (['', '/applicants', '/analytics', '/reports'] as $path) {
                $this->get($base.$path)->assertOk();
            }
        }
    }
}
