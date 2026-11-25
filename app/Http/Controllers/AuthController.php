<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account is inactive. Please contact an administrator.'])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $authUser = Auth::user();
            $authUser->update(['last_login_at' => now()]);
            log_activity('auth', 'login', 'User signed in to ERP.', $authUser, $authUser->id);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid email address or password.'])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $staffRole = Role::where('slug', 'staff')->first() ?? Role::first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role_id' => $staffRole?->id,
            'user_type' => 'staff',
            'status' => 'active',
            'password' => Hash::make($validated['password']),
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        log_activity('auth', 'register', 'New staff account registered.', $user, $user->id);

        return redirect()->route('dashboard')->with('success', 'Welcome to '.erp_setting('trade_name', 'Dreams ERP').'!');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'new_password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return back()->withErrors(['email' => 'No account found with this email address.'])->onlyInput('email');
        }

        if (! empty($validated['new_password'])) {
            $user->update(['password' => Hash::make($validated['new_password'])]);
            log_activity('auth', 'password_reset', 'Password reset completed via forgot-password screen.', $user, $user->id);

            return redirect()->route('login')->with('success', 'Password updated! You can now sign in with your new password.');
        }

        return back()->with('status', 'Account verified. Enter your new password below to complete reset.')->withInput();
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            log_activity('auth', 'logout', 'User signed out.', $user, $user->id);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
