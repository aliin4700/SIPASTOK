<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Helpers\ImageHelper;

class CustomerController extends Controller
{
    public function index()
    {
        $customer = Customer::with('user')->orderBy('id', 'desc')->get();

        return view('backend.v_customer.index', [
            'judul' => 'Customer',
            'sub' => 'Halaman Customer',
            'index' => $customer
        ]);
    }

    public function show(Customer $customer)
    {
        $customer->load(['user', 'orders']);

        return view('backend.v_customer.show', [
            'judul' => 'Customer',
            'sub' => 'Detail Customer',
            'show' => $customer,
        ]);
    }

    public function login()
    {
        return view('auth.login', [
            'judul' => 'Login BreadLab',
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

            $request->session()->regenerate();

            return redirect($this->redirectPathForRole((string) $user->role));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->with('error', 'Email atau password tidak sesuai.');
    }

    public function register()
    {
        return view('auth.register', [
            'judul' => 'Register Customer',
        ]);
    }

    public function storeRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => 'customer',
                'status' => 'active',
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
            ]);

            Customer::create([
                'user_id' => $user->id,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('beranda')->with('success', 'Registrasi berhasil. Selamat datang!');
    }

    // Redirect ke Google
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    // Callback dari Google
    public function callback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();

            $user = DB::transaction(function () use ($socialUser) {
                $registeredUser = User::where('email', $socialUser->email)->first();

                if (!$registeredUser) {
                    $registeredUser = User::create([
                        'name' => $socialUser->name,
                        'email' => $socialUser->email,
                        'role' => 'customer',
                        'status' => 'active',
                        'password' => Hash::make(str()->random(32)),
                    ]);
                }

                if ((string) $registeredUser->role !== 'customer') {
                    throw new \RuntimeException('Google login hanya untuk akun customer.');
                }

                Customer::updateOrCreate([
                    'user_id' => $registeredUser->id,
                ], [
                    'google_id' => $socialUser->id,
                    'google_token' => $socialUser->token,
                ]);

                return $registeredUser;
            });

            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->intended(route('beranda'));
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Terjadi kesalahan saat login dengan Google.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout(); // Logout pengguna
        $request->session()->invalidate(); // Hapus session
        $request->session()->regenerateToken(); // Regenerate token CSRF

        return redirect('/')->with('success', 'Anda telah berhasil logout.');
    }

    public function account(Request $request)
    {
        $customer = Customer::firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return view('v_customer.account', [
            'judul' => 'Customer',
            'subJudul' => 'Akun Customer',
            'customer' => $customer,
        ]);
    }

    public function updateAccount(Request $request)
    {
        $user = $request->user();
        $customer = Customer::firstOrCreate([
            'user_id' => $user->id,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'min:10', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'file', 'max:2048'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ], [
            'photo.image' => 'Format gambar gunakan file dengan ekstensi jpeg, jpg, png, gif, atau webp.',
            'photo.max' => 'Ukuran file gambar maksimal adalah 2048 KB.',
        ]);

        if ($request->file('photo')) {
            if ($user->photo) {
                $oldPhoto = $user->photo;
                $oldImagePath = str_contains($oldPhoto, '/')
                    ? public_path('storage/' . $oldPhoto)
                    : public_path('storage/img-customer/' . $oldPhoto);

                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $file = $request->file('photo');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/';
            ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);
            $validated['photo'] = 'img-customer/' . $originalFileName;
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'photo' => $validated['photo'] ?? $user->photo,
        ]);

        $customer->update([
            'address' => $validated['address'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
        ]);

        return redirect()->route('customer.account')->with('success', 'Data berhasil diperbarui');
    }

    private function redirectPathForRole(string $role): string
    {
        return match ($role) {
            'super_admin', 'admin' => '/backend/dashboard',
            'manager' => '/manager/dashboard',
            'cashier' => '/cashier',
            'staff_outlet' => '/staff/dashboard',
            'customer' => '/',
            default => '/',
        };
    }
}
