<?php

namespace App\Http\Controllers\Auth;

use App\Actions\ActivateAgentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivateAgentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentInvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $agent = $this->findInvitedAgent($token);

        if ($agent === null) {
            return redirect()
                ->route('agent.login')
                ->withErrors(['email' => 'This invitation link is invalid or has expired.']);
        }

        return view('auth.activate-agent', [
            'agent' => $agent,
            'token' => $token,
        ]);
    }

    public function store(string $token, ActivateAgentRequest $request, ActivateAgentAction $activateAgent): RedirectResponse
    {
        $agent = $this->findInvitedAgent($token);

        if ($agent === null) {
            return redirect()
                ->route('agent.login')
                ->withErrors(['email' => 'This invitation link is invalid or has expired.']);
        }

        $activated = $activateAgent->handle($agent, $request->validated('password'));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::login($activated);
        $request->session()->regenerate();

        return redirect()
            ->route('agent.dashboard')
            ->with('status', 'Your account is active. Welcome to Escalora.');
    }

    private function findInvitedAgent(string $token): ?User
    {
        $agent = User::query()
            ->where('invitation_token', hash('sha256', $token))
            ->where('role', 'agent')
            ->first();

        if ($agent === null || ! $agent->hasValidInvitation()) {
            return null;
        }

        return $agent;
    }
}
