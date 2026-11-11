<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['graduate', 'law', 'ipace', 'eteeap'] as $module) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => 'registrar', 'module' => $module],
                ['enabled' => true, 'can_edit' => false],
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('role', 'registrar')
            ->whereIn('module', ['graduate', 'law', 'ipace', 'eteeap'])
            ->update(['enabled' => false, 'can_edit' => false]);
    }
};
