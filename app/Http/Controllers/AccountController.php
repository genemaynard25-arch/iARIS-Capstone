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

    public function index()
    {
        $users = User::orderBy("name")
            ->get()
            ->map(fn(User $user) => $this->present($user))
            ->values()
            ->all();

        return view("accounts.index", [
            "roles" => $this->roles,
            "statuses" => $this->statuses,
            "departments" => $this->departments,
            "users" => $users,
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
        ]);

        $tempPassword = Str::password(12);

        $user = User::create([
            "name" => trim($validated["first"] . " " . $validated["last"]),
            "email" => $validated["email"],
            "password" => Hash::make($tempPassword),
            "role" => $validated["role"],
            "department" => $validated["department"],
            "status" => "Pending",
        ]);

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
        ]);

        $user->update([
            "name" => trim($validated["first"] . " " . $validated["last"]),
            "role" => $validated["role"],
            "department" => $validated["department"],
            "status" => $validated["status"],
        ]);

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

        $user->update(["status" => $validated["status"]]);

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

        $user->delete();

        return response()->json(["ok" => true]);
    }

    public function resetPassword(User $user)
    {
        if ($user->role === "admin") {
            abort(403, "The permanent administrator cannot be changed here.");
        }

        $tempPassword = Str::password(12);
        $user->update(["password" => Hash::make($tempPassword)]);

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
