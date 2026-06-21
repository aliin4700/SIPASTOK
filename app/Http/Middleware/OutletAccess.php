<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OutletAccess
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $allowedAbilities = ['can_monitor', 'can_cashier', 'can_manage_stock', 'can_manage_purchase', 'can_manage_finance'];

        if ($ability && !in_array($ability, $allowedAbilities, true)) {
            abort(403, 'Kemampuan akses outlet tidak valid.');
        }

        $outletId = $request->route('outlet')?->id
            ?? $request->route('outlet')
            ?? $request->input('outlet_id');

        if (!$outletId) {
            abort(403, 'Outlet belum dipilih.');
        }

        if ($request->user()?->hasOutletAccess($outletId, $ability)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses outlet ini.');
    }
}
