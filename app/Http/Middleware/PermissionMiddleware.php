<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if ($request->user()?->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki izin untuk aksi ini.');
    }
}
