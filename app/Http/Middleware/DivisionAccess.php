<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DivisionAccess
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $allowedAbilities = ['can_input_stock', 'can_input_waste', 'can_request_order'];

        if ($ability && !in_array($ability, $allowedAbilities, true)) {
            abort(403, 'Kemampuan akses divisi tidak valid.');
        }

        $outletId = $request->route('outlet')?->id
            ?? $request->route('outlet')
            ?? $request->input('outlet_id');

        $divisionId = $request->route('division')?->id
            ?? $request->route('division')
            ?? $request->input('division_id');

        if (!$outletId || !$divisionId) {
            abort(403, 'Outlet dan divisi belum dipilih.');
        }

        if ($request->user()?->hasDivisionAccess($outletId, $divisionId, $ability)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses divisi ini.');
    }
}
