<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware area customer.
 */
class IsCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Customer route tidak boleh diakses role internal seperti admin/cashier/staff.
        if (auth()->check() && (string) auth()->user()->role === 'customer') {
            return $next($request);
        }

        return redirect()->route('login')->with('msgError', 'Anda harus login sebagai customer');
    }
}
