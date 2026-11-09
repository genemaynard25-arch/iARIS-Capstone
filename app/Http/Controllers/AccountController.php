<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    protected array $roles = [
        "admin" => ["name" => "IATO Admin", "tone" => "primary"],
        "iato_staff" => ["name" => "IATO Staff", "tone" => "primary"],
        "program_chair" => ["name" => "Program Chair", "tone" => "info"],
        "dean" => ["name" => "Dean", "tone" => "info"],
        "principal" => ["name" => "SHS Principal", "tone" => "secondary"],
        "registrar" => ["name" => "Registrar", "tone" => "warning"],
        "lamp" => ["name" => "LAMP Office", "tone" => "secondary"],
        "president" => ["name" => "President", "tone" => "danger"],
        "vice_president" => ["name" => "Vice President", "tone" => "danger"],
        "chancellor" => ["name" => "Chancellor", "tone" => "danger"],
        "vice_chancellor" => ["name" => "Vice Chancellor", "tone" => "danger"],
        "toic" => ["name" => "TOIC", "tone" => "dark"],
        "director" => ["name" => "Director", "tone" => "info"],
    ];

    protected array $statuses = [
        "Active" => "primary",
        "Pending" => "warning",
        "Suspended" => "danger",
        "Inactive" => "secondary",
    ];

    protected array $departments = [
        "IATO",
        "CITE",
        "CEAS",
        "CIHTM",
        "CBEAM",
        "CON",
        "College of Law",
        "Graduate Programs",
        "Integrated School",
        "College Registrar",
        "IS Registrar",
        "Lasallian Mission Office",
        "Office of the President",
    ];

    protected array $colleges = [
        "CITE",
        "CBEAM",
        "CEAS",
        "CIHTM",
        "CON",
        "CCJE",
    ];

    public function index()
    {
        $users = User::orderBy("name")
            ->get()
            ->map(fn(User $user) => $this->present($user))
            ->values()
            ->all();

        return view("accounts.index", [
            "accessRequests" => \App\Support\AccessRequests::sample(),
            "roles" => $this->roles,
            "statuses" => $this->statuses,
            "departments" => $this->departments,
            "users" => $users,
            "colleges" => $this->colleges,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "first" => "required|string|max:100",
            "last" => "required|string|max:100",
            "email" => [
                "required",
                "email",
                "max:150",
                "unique:users,email",
                'regex:/@dlsl\.edu\.ph$/',
            ],
            "role" => [
                "required",
                Rule::in(array_diff(array_keys($this->roles), ["admin"])),
            ],
            "department" => ["required", Rule::in($this->departments)],
            "assigned_college" => ["nullable", Rule::in($this->colleges)],
        ]);

        $tempPassword = Str::password(12);

        $user = User::create([
            "name" => trim($validated["first"] . " " . $validated["last"]),
            "email" => $validated["email"],
            "password" => Hash::make($tempPassword),
            "role" => $validated["role"],
            "department" => $validated["department"],
            "status" => "Pending",
            "assigned_college" => in_array($validated["role"], ["dean", "program_chair"]) ? ($validated["assigned_college"] ?? null) : null,
        ]);

        \App\Models\AuditLog::record(
            'create',
            'User Accounts',
            'User Account Created',
            "Created account for **{$user->name}** ({$this->roles[$user->role]['name']})",
        );

        return response()->json([
            "ok" => true,
            "user" => $this->present($user),
            "tempPassword" => $tempPassword,
        ]);
    }

    public function update(Request $request, User $user)
    {
        if ($user->role === "admin") {
            abort(403, "The permanent administrator cannot be changed here.");
        }

        $validated = $request->validate([
            "first" => "required|string|max:100",
            "last" => "required|string|max:100",
            "role" => [
                "required",
                Rule::in(array_diff(array_keys($this->roles), ["admin"])),
            ],
            "department" => ["required", Rule::in($this->departments)],
            "status" => ["required", Rule::in(array_keys($this->statuses))],
            "assigned_college" => ["nullable", Rule::in($this->colleges)],
        ]);

        $before = $user->only(['name', 'role', 'department', 'status', 'assigned_college']);

        $user->update([
            "name" => trim($validated["first"] . " " . $validated["last"]),
            "role" => $validated["role"],
            "department" => $validated["department"],
            "status" => $validated["status"],
            "assigned_college" => in_array($validated["role"], ["dean", "program_chair"]) ? ($validated["assigned_college"] ?? null) : null,
        ]);

        $diff = collect($before)
            ->map(fn ($value, $field) => $value != $user->{$field}
                ? ['field' => ucfirst(str_replace('_', ' ', $field)), 'before' => $value ?? '—', 'after' => $user->{$field} ?? '—']
                : null)
            ->filter()->values()->all();

        \App\Models\AuditLog::record('edit', 'User Accounts', 'User Account Edited', "Updated account for **{$user->name}**", null, $diff);

        return response()->json([
            "ok" => true,
            "user" => $this->present($user),
        ]);
    }

    public function setStatus(Request $request, User $user)
    {
        if ($user->role === "admin") {
            abort(403, "The permanent administrator cannot be changed here.");
        }

        $validated = $request->validate([
            "status" => ["required", Rule::in(array_keys($this->statuses))],
        ]);

        $before = $user->status;
        $user->update(["status" => $validated["status"]]);

        $titles = [
            'Suspended' => 'Account Suspended',
            'Inactive' => 'Account Deactivated',
            'Active' => 'Account Reactivated',
            'Pending' => 'Account Status Changed',
        ];

        \App\Models\AuditLog::record(
            'edit',
            'User Accounts',
            $titles[$validated['status']] ?? 'Account Status Changed',
            "Changed status of **{$user->name}** from **{$before}** to **{$validated['status']}**",
        );

        return response()->json([
            "ok" => true,
            "user" => $this->present($user),
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->role === "admin") {
            abort(403, "The permanent administrator cannot be changed here.");
        }

        $name = $user->name;
        $user->delete();

        \App\Models\AuditLog::record('delete', 'User Accounts', 'User Account Deleted', "Deleted the account of **{$name}**");

        return response()->json(["ok" => true]);
    }

    public function resetPassword(User $user)
    {
        if ($user->role === "admin") {
            abort(403, "The permanent administrator cannot be changed here.");
        }

        $tempPassword = Str::password(12);
        $user->update(["password" => Hash::make($tempPassword)]);

        \App\Models\AuditLog::record('edit', 'User Accounts', 'Password Reset Forced', "Forced a password reset for **{$user->name}**");

        return response()->json([
            "ok" => true,
            "tempPassword" => $tempPassword,
        ]);
    }

    protected function present(User $user): array
    {
        $parts = preg_split("/\s+/", trim($user->name));
        $last = array_pop($parts);
        $first = implode(" ", $parts) ?: $last;

        return [
            "id" => $user->id,
            "first" => $first,
            "last" => $last,
            "email" => $user->email,
            "role" => $user->role,
            "department" => $user->department,
            "status" => $user->status,
            "mfa" => $user->two_factor_confirmed_at !== null,
            "lastLogin" => $user->last_login_at?->toIso8601String(),
            "joined" => $user->created_at?->toDateString(),
            "permanent" => $user->role === "admin",
        ];
    }
}