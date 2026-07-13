<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware permission sederhana tanpa package tambahan.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Permission dibaca dari relasi role_permissions melalui User::hasPermission().
        if ($request->user()?->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki izin untuk aksi ini.');
    }
}
