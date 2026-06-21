<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasAnyRole(['super_admin', 'admin', 'manager'])) {
            return $next($request);
        }

        abort(403, 'Data finance hanya dapat diakses oleh manager, admin, dan super admin.');
    }
}
