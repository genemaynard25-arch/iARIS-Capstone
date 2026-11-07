<?php

namespace App\Http\Controllers;

use App\Models\User;

class SuccessionController extends Controller
{
    /**
     * Super User Succession: hand the Super Admin role to another account in four steps
     * (nominate, nominee accepts, you confirm, role moves).
     *
     * The accounts are real (users table). Who can be nominated: Active accounts with MFA set up.
     * FRONT END ONLY: the steps run on the page; nothing is changed in the database.
     * TODO (backend): a successions table and routes for nominate / accept / confirm / cancel,
     * each checking the password and a fresh MFA code.
     */
    public function index()
    {
        $me = auth()->user();
        // Only the real Super Admin (not a Temporary Admin or the SHS principal seeded as admin)
        abort_unless($me->role === 'admin' && ! $me->isShsPrincipal(), 403, 'Only the Super Admin can hand over the role.');

        $accounts = User::where('id', '!=', $me->id)
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $mfa = $u->two_factor_confirmed_at !== null;
                $status = $u->status ?? 'Active';

                // Another admin account (e.g. the seeded SHS principal) is already an admin, so can't be nominated
                $alreadyAdmin = $u->role === 'admin';

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->roleInfo()[0],
                    'office' => $u->roleInfo()[1],
                    'eligible' => ! $alreadyAdmin && $status === 'Active' && $mfa,
                    'why' => match (true) {
                        $alreadyAdmin => 'Already an admin',
                        $status !== 'Active' => $status === 'Pending' ? 'Not activated yet' : $status,
                        default => $mfa ? 'Eligible' : 'MFA not set up',
                    },
                ];
            })
            ->sortByDesc('eligible')
            ->values();

        return view('succession.index', [
            'me' => ['name' => $me->name, 'email' => $me->email, 'since' => $me->created_at?->format('M j, Y') ?? '—'],
            'accounts' => $accounts,
        ]);
    }
}
