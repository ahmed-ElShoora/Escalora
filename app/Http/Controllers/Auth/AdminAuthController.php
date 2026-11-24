<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterAdminAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Requests\Auth\AdminRegisterRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(AdminRegisterRequest $request, RegisterAdminAction $registerAdmin): RedirectResponse
    {
        $admin = $registerAdmin->handle($request->validated());

        Auth::login($admin);

        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('status', 'Account created. Please verify your email to open the admin dashboard.');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt([...$credentials, 'role' => UserRole::Admin->value], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match an admin account.']);
        }

        $user = Auth::user();

        if ($user === null || ! $user->isActive()) {
            Auth::logout();

            return back()->withErrors(['email' => 'This admin account is not active.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
