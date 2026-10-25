<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (empty($roles)) {
            return $next($request);
        }

        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        $allowedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $allowedRoles[] = trim($r);
            }
        }

        if (! in_array($userRole, $allowedRoles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Akses Ditolak: Peran akun Anda ('.$user->role->label().') tidak memiliki izin untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
