<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $entries = AuditLog::with('user')->latest()->take(500)->get();

        $people = $entries->pluck('user')->filter()->unique('id')->mapWithKeys(fn ($u) => [
            (string) $u->id => [
                'name' => $u->displayName(),
                'role' => $u->roleInfo()[0],
                'tone' => 'primary',
                'ip' => '',
            ],
        ]);

        $logs = $entries->map(fn (AuditLog $log) => [
            'id' => $log->id,
            'user' => (string) $log->user_id,
            'action' => $log->action,
            'module' => $log->module,
            'at' => $log->created_at->toIso8601String(),
            'ip' => $log->ip_address,
            'title' => $log->title,
            'details' => $log->details,
            'description' => $log->description,
            'diff' => $log->diff ?? [],
        ])->values();

        return view('audit-logs.index', [
            'people' => $people,
            'actions' => [
                'login' => ['label' => 'Login', 'icon' => 'bi-box-arrow-in-right', 'tone' => 'primary', 'group' => 'session'],
                'logout' => ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'tone' => 'secondary', 'group' => 'session'],
                'create' => ['label' => 'Create', 'icon' => 'bi-plus-lg', 'tone' => 'info', 'group' => 'create'],
                'edit' => ['label' => 'Edit', 'icon' => 'bi-pencil', 'tone' => 'warning', 'group' => 'edit'],
                'delete' => ['label' => 'Delete', 'icon' => 'bi-trash', 'tone' => 'danger', 'group' => 'delete'],
                'export' => ['label' => 'Export', 'icon' => 'bi-download', 'tone' => 'dark', 'group' => 'export'],
                'import' => ['label' => 'Import', 'icon' => 'bi-cloud-arrow-up', 'tone' => 'info', 'group' => 'import'],
                'permission' => ['label' => 'Permission Change', 'icon' => 'bi-shield-lock', 'tone' => 'danger', 'group' => 'permission'],
                'report' => ['label' => 'Report Generated', 'icon' => 'bi-file-earmark-text', 'tone' => 'primary', 'group' => 'report'],
            ],
            'actionGroups' => [
                'session' => 'Login / Logout', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete',
                'export' => 'Export', 'import' => 'Import', 'permission' => 'Permission', 'report' => 'Report',
            ],
            'modules' => [
                'Applicants', 'College Records', 'Integrated School', 'Scholars', 'Graduate Programs', 'College of Law',
                'iPACE', 'ETEEAP', 'LAMP', 'Reports', 'Analytics', 'Access Control', 'User Accounts', 'Import Data', 'System',
            ],
            'stats' => [
                ['key' => 'today', 'label' => 'Total Entries Today', 'sub' => 'All action types', 'tone' => 'primary'],
                ['key' => 'delete', 'label' => 'Deletions', 'sub' => 'Records removed today', 'tone' => 'danger'],
                ['key' => 'permission', 'label' => 'Permission Changes', 'sub' => 'Access control edits today', 'tone' => 'warning'],
                ['key' => 'export', 'label' => 'Exports', 'sub' => 'Files exported today', 'tone' => 'dark'],
            ],
            'logs' => $logs,
        ]);
    }
}