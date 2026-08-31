<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * F6, F66: Membatasi akses berdasarkan peran pengguna
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        // Check if user has any of the required roles
        if (!$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to access this resource.',
                'required_roles' => $roles,
                'your_role' => $request->user()->role,
            ], 403);
        }

        return $next($request);
    }
}