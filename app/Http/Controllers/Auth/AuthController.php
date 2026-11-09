<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $candidate = User::where('email', $credentials['email'])->first();

        if (! Auth::attempt($credentials + ['is_active' => true])) {
            AuditService::log(
                $candidate,
                'authentication',
                'login',
                reason: 'Failed login attempt',
            );

            return back()
                ->withErrors(['email' => 'Email atau password tidak valid, atau akun tidak aktif.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        AuditService::log(Auth::user(), 'authentication', 'login');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        AuditService::log($user, 'authentication', 'logout');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Anda berhasil keluar.');
    }

    public function showProfile(): View
    {
        return view('auth.profile', ['user' => request()->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->getKey()],
            'phone' => ['nullable', 'string', 'max:255'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $oldValues = $user->only(['name', 'email', 'phone']);
        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        AuditService::log(
            $user,
            'authentication',
            'update',
            $user,
            $oldValues,
            $user->only(['name', 'email', 'phone']),
        );

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
