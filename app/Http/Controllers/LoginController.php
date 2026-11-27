<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller autentikasi satu pintu SIPASTOK (Admin, Manager, dan Staff Outlet).
 */
class LoginController extends Controller
{
    public function login()
    {
        return view('auth.login', [
            'judul' => 'Login SIPASTOK',
        ]);
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if ((string) $user->status !== 'active') {
                Auth::logout();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('error', 'Akun belum aktif.');
            }

            if (! $user->hasAnyRole(['super_admin', 'admin', 'manager', 'staff_outlet'])) {
                Auth::logout();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->with('error', 'Akun ini tidak memiliki akses ke sistem SIPASTOK.');
            }

            $request->session()->regenerate();

            return redirect()->intended($this->redirectPathForRole((string) $user->role));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->with('error', 'Email atau password tidak sesuai.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    public function logoutBackend(Request $request)
    {
        return $this->logout($request);
    }

    private function redirectPathForRole(string $role): string
    {
        return match ($role) {
            'super_admin', 'admin' => route('backend.dashboard'),
            'manager' => route('manager.dashboard'),
            'staff_outlet' => route('staff.dashboard'),
            default => route('login'),
        };
    }
}
