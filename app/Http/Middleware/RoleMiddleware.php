<?php

namespace App\Http\Middleware;

use App\Models\AuditTrail;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName() ?? $request->path();

        if (! $user) {
            AuditTrail::query()->create([
                'user_id' => null,
                'action' => 'UNAUTHENTICATED_ACCESS_BLOCKED',
                'target_type' => 'route',
                'target_id' => null,
                'details' => [
                    'route' => $routeName,
                    'required_roles' => $roles,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            abort(401);
        }

        if (! in_array($user->role, $roles, true)) {
            AuditTrail::query()->create([
                'user_id' => $user->id,
                'action' => 'ROLE_FORBIDDEN_ACCESS_BLOCKED',
                'target_type' => 'route',
                'target_id' => null,
                'details' => [
                    'route' => $routeName,
                    'user_role' => $user->role,
                    'required_roles' => $roles,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            abort(403, 'Role anda tidak diizinkan untuk aksi ini.');
        }

        return $next($request);
    }
}
