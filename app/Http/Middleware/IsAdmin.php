<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && in_array((string) auth()->user()->role, ['admin', 'super_admin'], true)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses admin.');
    }
}
