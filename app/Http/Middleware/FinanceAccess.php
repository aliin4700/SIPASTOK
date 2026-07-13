<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melindungi halaman finance dari cashier, staff outlet, dan customer.
 */
class FinanceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Finance boleh dilihat manager dan dikelola oleh admin/super_admin.
        if ($request->user()?->hasAnyRole(['super_admin', 'admin', 'manager'])) {
            return $next($request);
        }

        abort(403, 'Data finance hanya dapat diakses oleh manager, admin, dan super admin.');
    }
}
