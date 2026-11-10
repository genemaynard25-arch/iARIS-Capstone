<?php

namespace App\Http\Controllers;

use App\Models\AccessRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccessRequestController extends Controller
{
    /** Login page's "Request Access" form. No auth: anyone can ask to be reviewed. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "first" => "required|string|max:100",
            "last" => "required|string|max:100",
            "email" => ["required", "string", "email", "max:150"],
            "password" => ["required", "string", Password::default()],
            "position" => "required|string|max:150",
            "department" => "required|string|max:150",
            "reason" => "required|string|max:255",
        ]);

        if (User::where('email', $validated['email'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }

        if (AccessRequest::where('email', $validated['email'])->where('status', 'waiting')->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => 'A request for this email is already waiting for review.',
            ]);
        }

        AccessRequest::create([
            "first_name" => $validated["first"],
            "last_name" => $validated["last"],
            "email" => $validated["email"],
            "password" => Hash::make($validated["password"]),
            "position" => $validated["position"],
            "department" => $validated["department"],
            "reason" => $validated["reason"],
        ]);

        return response()->json(["ok" => true]);
    }

    /** Approve: creates the real account with the role/department the admin confirms. */
    public function approve(Request $request, AccessRequest $accessRequest)
    {
        if ($accessRequest->status !== 'waiting') {
            abort(409, 'This request was already decided.');
        }

        $validated = $request->validate([
            "role" => ["required", Rule::in(array_diff(array_keys(AccountController::ROLES), ["admin"]))],
            "department" => ["required", Rule::in(AccountController::DEPARTMENTS)],
            "assigned_college" => ["nullable", Rule::in(AccountController::COLLEGES)],
        ]);

        if (User::where('email', $accessRequest->email)->exists()) {
            abort(409, 'An account with this email already exists.');
        }

        $user = User::create([
            "name" => trim("{$accessRequest->first_name} {$accessRequest->last_name}"),
            "email" => $accessRequest->email,
            "password" => $accessRequest->password, // already hashed, chosen by the requester
            "role" => $validated["role"],
            "department" => $validated["department"],
            "status" => "Pending",
            "assigned_college" => in_array($validated["role"], ["dean", "program_chair"]) ? ($validated["assigned_college"] ?? null) : null,
            "assigned_level" => $validated["role"] === "registrar"
                ? ($validated["department"] === "IS Registrar" ? "is" : "college")
                : null,
            "access_type" => $validated["role"] === "iato_staff" ? "editor" : "viewer",
        ]);

        $accessRequest->update([
            "status" => "approved",
            "decided_by" => $request->user()->id,
            "decided_at" => now(),
        ]);

        AuditLog::record(
            'create',
            'User Accounts',
            'Access Request Approved',
            "Approved **{$accessRequest->name()}**'s access request and created an account (**" . AccountController::ROLES[$user->role]['name'] . "**)",
        );

        return response()->json(["ok" => true]);
    }

    public function decline(Request $request, AccessRequest $accessRequest)
    {
        if ($accessRequest->status !== 'waiting') {
            abort(409, 'This request was already decided.');
        }

        $validated = $request->validate([
            "note" => "nullable|string|max:255",
        ]);

        $accessRequest->update([
            "status" => "declined",
            "decided_by" => $request->user()->id,
            "decided_at" => now(),
            "note" => $validated["note"] ?? null,
        ]);

        AuditLog::record(
            'edit',
            'User Accounts',
            'Access Request Declined',
            "Declined **{$accessRequest->name()}**'s access request" . ($validated["note"] ? ": {$validated['note']}" : ''),
        );

        return response()->json(["ok" => true]);
    }
}
