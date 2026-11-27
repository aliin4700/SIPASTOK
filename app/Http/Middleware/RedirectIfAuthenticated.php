<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                return redirect(match ((string) $user->role) {
                    'super_admin', 'admin' => route('backend.dashboard'),
                    'manager' => route('manager.dashboard'),
                    'staff_outlet' => route('staff.dashboard'),
                    default => route('staff.dashboard'),
                });
            }
        }

        return $next($request);
    }
}
