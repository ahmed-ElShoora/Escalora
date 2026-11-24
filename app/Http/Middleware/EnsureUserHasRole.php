<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $requiredRole = UserRole::from($role);

        if ($user === null || $user->role !== $requiredRole || ! $user->isActive()) {
            abort(403, 'You are not authorized to access this area.');
        }

        return $next($request);
    }
}
