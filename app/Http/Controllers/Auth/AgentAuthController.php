<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AgentLoginRequest;
use App\Services\SupportQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.agent-login');
    }

    public function login(AgentLoginRequest $request, SupportQueueService $queue): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt([
            ...$credentials,
            'role' => UserRole::Agent->value,
            'status' => UserStatus::Active->value,
        ], $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match an active agent account.']);
        }

        $request->session()->regenerate();
        $agent = $request->user();
        $agent->forceFill([
            'is_available' => true,
            'last_seen_at' => now(),
        ])->save();

        if ($agent->workspace !== null) {
            $queue->dispatchWaiting($agent->workspace);
        }

        return redirect()->intended(route('agent.dashboard'));
    }

    public function logout(Request $request, SupportQueueService $queue): RedirectResponse
    {
        $agent = $request->user();
        $workspace = $agent?->workspace;

        $agent?->forceFill([
            'is_available' => false,
            'last_seen_at' => now(),
        ])->save();

        if ($workspace !== null) {
            $queue->dispatchWaiting($workspace);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent.login');
    }
}
