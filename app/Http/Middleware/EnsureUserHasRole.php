<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Gates a role's own dashboard (e.g. /lamp, /shs, /executive). Each $check is either a plain
// role name ('registrar') or, for roles that need more than a role column match (the SHS
// principal, the Executive Office's several roles, ...), the name of a boolean method already
// on User (e.g. 'isShsPrincipal', 'isExecutive'). Admins always pass, so they can still preview
// every role's pages (see layouts/app.blade.php's sidebar-picking comment).
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$checks): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $next($request);
        }

        foreach ($checks as $check) {
            $allowed = str_starts_with($check, 'is') && method_exists($user, $check)
                ? $user->{$check}()
                : $user->role === $check;

            if ($allowed) {
                return $next($request);
            }
        }

        abort(403, 'Your role does not have access to this page.');
    }
}
