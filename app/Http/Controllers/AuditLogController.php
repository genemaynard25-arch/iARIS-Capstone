<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private const ACTION_GROUPS = [
        'session' => ['login', 'logout'], 'create' => ['create'], 'edit' => ['edit', 'update'], 'delete' => ['delete'],
        'export' => ['export'], 'import' => ['import'], 'permission' => ['permission'], 'report' => ['report'],
    ];

    private function filteredQuery(array $filters): Builder
    {
        $query = AuditLog::query();
        if (! empty($filters['user'])) {
            $query->where('user_id', $filters['user']);
        }
        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (! empty($filters['group'])) {
            $query->whereIn('action', self::ACTION_GROUPS[$filters['group']]);
        }
        $days = $filters['days'] ?? '';
        if (in_array($days, ['1', '7', '30'], true)) {
            $query->whereBetween('created_at', [now()->startOfDay()->subDays((int) $days - 1), now()->endOfDay()]);
        } elseif ($days === 'ay') {
            $year = now()->month >= 8 ? now()->year : now()->year - 1;
            $query->where('created_at', '>=', now()->setDate($year, 8, 1)->startOfDay());
        } elseif ($days === 'custom') {
            if (! empty($filters['from'])) {
                $query->where('created_at', '>=', $filters['from'].' 00:00:00');
            }
            if (! empty($filters['to'])) {
                $query->where('created_at', '<=', $filters['to'].' 23:59:59');
            }
        }
        if (! empty($filters['search'])) {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['search'])).'%';
            $query->where(function ($q) use ($like) {
                foreach (['id', 'actor_name', 'actor_role', 'title', 'details', 'description', 'module', 'action', 'ip_address'] as $column) {
                    $q->orWhereRaw($column." LIKE ? ESCAPE '!'", [$like]);
                }
                $q->orWhereHas('user', fn ($user) => $user->whereRaw("name LIKE ? ESCAPE '!'", [$like])->orWhereRaw("role LIKE ? ESCAPE '!'", [$like]));
            });
        }

        return $query;
    }

    private static function shape(AuditLog $log): array
    {
        return [
            'id' => $log->id, 'user' => (string) $log->user_id,
            'actorName' => $log->actor_name, 'actorRole' => $log->actor_role,
            'action' => $log->action === 'update' ? 'edit' : $log->action, 'module' => $log->module,
            'at' => $log->created_at->toIso8601String(), 'ip' => $log->ip_address,
            'title' => $log->title, 'details' => $log->details ?? '',
            'description' => $log->description, 'diff' => $log->diff ?? [],
        ];
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:150', 'user' => 'nullable|integer|min:1', 'module' => 'nullable|string|max:100',
            'group' => 'nullable|in:session,create,edit,delete,export,import,permission,report',
            'days' => 'nullable|in:1,7,30,ay,custom', 'from' => 'nullable|date_format:Y-m-d',
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'page' => 'nullable|integer|min:1', 'export' => 'nullable|in:csv',
        ]);
        $query = $this->filteredQuery($filters);
        if (($filters['export'] ?? '') === 'csv') {
            $query->where('id', '<=', AuditLog::max('id') ?? 0);
            AuditLog::record('export', 'System', 'Audit Logs Exported', 'Exported audit entries matching the selected filters.');

            return response()->streamDownload(function () use ($query) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['Log ID', 'User', 'Role', 'Action', 'Module', 'Details', 'IP Address', 'Timestamp'], escape: '');
                $actors = [];
                foreach ($query->with('user')->latest()->orderByDesc('id')->lazy(500) as $log) {
                    $actors[$log->user_id ?? 0] ??= [$log->user?->displayName() ?? 'Deleted User', $log->user?->roleInfo()[0] ?? ''];
                    $values = ['LOG-'.str_pad((string) $log->id, 6, '0', STR_PAD_LEFT), $log->actor_name ?: $actors[$log->user_id ?? 0][0], $log->actor_role ?: $actors[$log->user_id ?? 0][1], match ($log->action) {
                        'permission' => 'Permission Change', 'report' => 'Report Generated', 'update' => 'Edit', default => ucfirst($log->action)
                    }, $log->module, str_replace('**', '', $log->details ?? ''), $log->ip_address, $log->created_at->toIso8601String()];
                    fputcsv($stream, array_map(fn ($v) => preg_match('/^[\s]*[=+@-]/', (string) $v) ? "'".$v : $v, $values), escape: '');
                }
                fclose($stream);
            }, 'iaris-audit-logs-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        $paginator = $query->latest()->orderByDesc('id')->paginate(10);
        if ($paginator->currentPage() > $paginator->lastPage()) {
            $paginator = $query->paginate(10, ['*'], 'page', $paginator->lastPage());
        }
        $people = User::whereIn('id', AuditLog::select('user_id')->whereNotNull('user_id')->distinct())->get()->mapWithKeys(fn ($u) => [(string) $u->id => ['name' => $u->displayName(), 'role' => $u->roleInfo()[0], 'tone' => 'primary', 'ip' => '']]);
        $logs = $paginator->getCollection()->map(fn ($log) => self::shape($log))->values();
        $pagination = ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage(), 'total' => $paginator->total(), 'from' => $paginator->firstItem(), 'to' => $paginator->lastItem()];
        $today = AuditLog::whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
        $totals = ['today' => (clone $today)->count()];
        foreach (['delete', 'permission', 'export'] as $action) {
            $totals[$action] = (clone $today)->where('action', $action)->count();
        }
        if ($request->expectsJson()) {
            return response()->json(['logs' => $logs, 'people' => $people, 'pagination' => $pagination, 'totals' => $totals]);
        }

        return view('audit-logs.index', [
            'pagination' => $pagination, 'filters' => $filters, 'totals' => $totals,
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
