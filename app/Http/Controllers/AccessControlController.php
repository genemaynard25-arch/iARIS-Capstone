<?php

namespace App\Http\Controllers;

class AccessControlController extends Controller
{
    /**
     * Access Control page (admin only once RBAC is in place).
     *
     * Placeholder data so the UI can be built first, same idea as the other
     * pages. Names are made up. Later the matrix would come from a
     * role_permissions table, the temporary admin from a temp_admins table,
     * and the log from an audit log table.
     *
     * Role keys match the 'role' column on users (admin, iato_staff, dean, ...).
     * For each module, 'on' lists the roles that have access. IATO Admin always
     * has access, so it isn't listed. 'adminOnly' modules can't be given to
     * anyone else, so their switches are locked off.
     */
    public function index()
    {
        $roles = [
            [
                "key" => "admin",
                "name" => "IATO Admin",
                "icon" => "bi-star-fill",
                "tone" => "primary",
            ],
            [
                "key" => "iato_staff",
                "name" => "IATO Staff",
                "icon" => "bi-person-badge",
                "tone" => "primary",
            ],
            [
                "key" => "program_chair",
                "name" => "Program Chair",
                "icon" => "bi-person-video2",
                "tone" => "info",
            ],
            [
                "key" => "dean",
                "name" => "Dean",
                "icon" => "bi-person-workspace",
                "tone" => "info",
            ],
            [
                "key" => "principal",
                "name" => "SHS Principal",
                "icon" => "bi-building",
                "tone" => "secondary",
            ],
            [
                "key" => "registrar",
                "name" => "Registrar",
                "icon" => "bi-person-vcard",
                "tone" => "warning",
            ],
            [
                "key" => "lamp",
                "name" => "LAMP Office",
                "icon" => "bi-award",
                "tone" => "secondary",
            ],
            [
                "key" => "president",
                "name" => "President",
                "icon" => "bi-bank",
                "tone" => "danger",
            ],
            [
                "key" => "vice_president",
                "name" => "Vice President",
                "icon" => "bi-bank",
                "tone" => "danger",
            ],
            [
                "key" => "chancellor",
                "name" => "Chancellor",
                "icon" => "bi-bank",
                "tone" => "danger",
            ],
            [
                "key" => "vice_chancellor",
                "name" => "Vice Chancellor",
                "icon" => "bi-bank",
                "tone" => "danger",
            ],
        ];

        $permissions = \App\Models\RolePermission::where("enabled", true)
            ->get()
            ->groupBy("module")
            ->map(fn($rows) => $rows->pluck("role")->all());

        $modules = [
            "Main" => [
                [
                    "key" => "dashboard",
                    "name" => "Dashboard",
                    "description" => "Overview stats and charts",
                    "on" => $permissions->get("dashboard", []),
                ],
                [
                    "key" => "applicants",
                    "name" => "Applicants",
                    "description" => "View and search applicant records",
                    "on" => $permissions->get("applicants", []),
                ],
                [
                    "key" => "reports",
                    "name" => "Reports",
                    "description" => "Generate and download reports",
                    "on" => $permissions->get("reports", []),
                ],
                [
                    "key" => "analytics",
                    "name" => "Analytics",
                    "description" => "View charts and AI insights",
                    "on" => $permissions->get("analytics", []),
                ],
            ],
            "Records" => [
                [
                    "key" => "college",
                    "name" => "College Records",
                    "description" => "View college applicant records",
                    "on" => $permissions->get("college", []),
                ],
                [
                    "key" => "is",
                    "name" => "Integrated School Records",
                    "description" => "View IS applicant records",
                    "on" => $permissions->get("is", []),
                ],
                [
                    "key" => "scholars",
                    "name" => "Scholars Records",
                    "description" => "View scholarship applicant data",
                    "on" => $permissions->get("scholars", []),
                ],
                [
                    "key" => "import",
                    "name" => "Import Data",
                    "description" => "Upload Excel/CSV records",
                    "on" => $permissions->get("import", []),
                ],
            ],
            "System" => [
                [
                    "key" => "edit",
                    "name" => "Edit & Review Records",
                    "description" => "Edit applicant data before publishing",
                    "on" => $permissions->get("edit", []),
                ],
                [
                    "key" => "access",
                    "name" => "Access Control",
                    "description" => "Manage role permissions",
                    "on" => [],
                    "adminOnly" => true,
                ],
                [
                    "key" => "accounts",
                    "name" => "User Accounts",
                    "description" => "Manage user accounts and roles",
                    "on" => [],
                    "adminOnly" => true,
                ],
                [
                    "key" => "audit",
                    "name" => "Audit Logs",
                    "description" => "View system activity history",
                    "on" => $permissions->get("audit", []),
                ],
            ],
        ];
        $activeTempAdmin = \App\Models\TempAdmin::active()
            ->with(["user", "grantedBy"])
            ->first();

        $tempAdmin = $activeTempAdmin
            ? [
                "name" => $activeTempAdmin->user->name,
                "initials" => $this->initials($activeTempAdmin->user->name),
                "role" => "IATO Staff",
                "email" => $activeTempAdmin->user->email,
                "grantedBy" => $activeTempAdmin->grantedBy->name ?? "Unknown",
                "grantedOn" => $activeTempAdmin->created_at->format("M j, Y"),
                "expires" => $activeTempAdmin->expires_at->format(
                    'M j, Y \a\t g:i A',
                ),
            ]
            : null;

        return view("access-control.index", [
            "roles" => $roles,
            "modules" => $modules,

            "permanentAdmin" => [
                "name" => auth()->user()->name,
                "isRealAdmin" => auth()->user()->role === "admin",
            ],
            "myActiveTempGrant" => auth()->user()->activeTempAdminGrant(),
            "tempAdmin" => $tempAdmin,

            // Staff who can be picked in the "Assign Temporary Admin" pop-up
            "staffOptions" => \App\Models\User::where("role", "iato_staff")
                ->get()
                ->map(
                    fn($u) => [
                        "id" => $u->id,
                        "name" => $u->name,
                        "role" => "IATO Staff",
                        "email" => $u->email,
                    ],
                )
                ->values()
                ->all(),

            // Change types: label => Bootstrap colour for its badge
            "changeTypes" => [
                "Granted" => "primary",
                "Revoked" => "danger",
                "Modified" => "warning",
                "Temporary" => "info",
            ],

            "log" => [
                [
                    "action" => "Temporary admin access assigned",
                    "meta" =>
                        "Cruz, Jasmine granted admin-level permissions until Jun 14, 2026",
                    "affected" => "IATO Staff",
                    "module" => "All Modules",
                    "type" => "Temporary",
                    "by" => "R. Renegado",
                    "at" => "Jun 10, 2026 · 9:00 AM",
                ],
                [
                    "action" => "Analytics access granted",
                    "meta" =>
                        "Dean / Program Chair role can now view Analytics",
                    "affected" => "Dean / Chair",
                    "module" => "Analytics",
                    "type" => "Granted",
                    "by" => "R. Renegado",
                    "at" => "Jun 8, 2026 · 10:22 AM",
                ],
                [
                    "action" => "Applicants access revoked",
                    "meta" =>
                        "LAMP Office can no longer view the Applicants module",
                    "affected" => "LAMP Office",
                    "module" => "Applicants",
                    "type" => "Revoked",
                    "by" => "R. Renegado",
                    "at" => "Jun 5, 2026 · 2:14 PM",
                ],
                [
                    "action" => "Temporary admin period extended",
                    "meta" =>
                        "Santos, Marco extended from May 30 to Jun 2, 2026",
                    "affected" => "IATO Staff",
                    "module" => "All Modules",
                    "type" => "Modified",
                    "by" => "R. Renegado",
                    "at" => "Jun 4, 2026 · 8:30 AM",
                ],
                [
                    "action" => "IS Records access granted",
                    "meta" =>
                        "Registrar role can now view Integrated School records",
                    "affected" => "Registrar",
                    "module" => "IS Records",
                    "type" => "Granted",
                    "by" => "R. Renegado",
                    "at" => "Jun 3, 2026 · 11:05 AM",
                ],
                [
                    "action" => "Import Data access revoked",
                    "meta" =>
                        "Dean / Chair role can no longer import data files",
                    "affected" => "Dean / Chair",
                    "module" => "Import Data",
                    "type" => "Revoked",
                    "by" => "R. Renegado",
                    "at" => "May 28, 2026 · 3:40 PM",
                ],
            ],
        ]);
    }

    public function assignTempAdmin(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            "user_id" => "required|exists:users,id",
            "starts_at" => "required|date",
            "expires_at" => "required|date|after:starts_at",
            "reason" => "required|string|max:255",
        ]);

        \App\Models\TempAdmin::active()->update(["revoked_at" => now()]);

        \App\Models\TempAdmin::create([
            "user_id" => $validated["user_id"],
            "granted_by" => $request->user()->id,
            "reason" => $validated["reason"],
            "starts_at" => $validated["starts_at"],
            "expires_at" => $validated["expires_at"],
        ]);

        return redirect("/access-control");
    }

    public function revokeTempAdmin(\Illuminate\Http\Request $request)
    {
        \App\Models\TempAdmin::active()->update(["revoked_at" => now()]);

        return response()->json(["ok" => true]);
    }

    public function savePermissions(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            "changes" => "required|array",
            "changes.*.role" => "required|string",
            "changes.*.module" => "required|string",
            "changes.*.enabled" => "required|boolean",
        ]);

        foreach ($validated["changes"] as $change) {
            \App\Models\RolePermission::updateOrCreate(
                ["role" => $change["role"], "module" => $change["module"]],
                ["enabled" => $change["enabled"]],
            );
        }

        return response()->json(["ok" => true]);
    }

    protected function initials(string $name): string
    {
        $parts = preg_split("/\s+/", trim($name));
        $letters = array_map(
            fn($p) => mb_substr($p, 0, 1),
            array_slice($parts, 0, 2),
        );

        return strtoupper(implode("", $letters));
    }
}
