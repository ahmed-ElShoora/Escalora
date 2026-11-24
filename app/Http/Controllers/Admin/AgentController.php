<?php

namespace App\Http\Controllers\Admin;

use App\Actions\InviteAgentAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgentRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SupportQueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(Request $request): View
    {
        $agents = User::query()
            ->where('company_id', $request->user()->company_id)
            ->where('role', UserRole::Agent)
            ->with('workspace')
            ->latest()
            ->paginate(12);

        return view('admin.agents.index', compact('agents'));
    }

    public function store(
        StoreAgentRequest $request,
        Workspace $workspace,
        InviteAgentAction $inviteAgent,
    ): RedirectResponse {
        $inviteAgent->handle($workspace, $request->validated(), $request->user());

        return back()->with('status', 'Invitation sent. The agent will receive an email to activate their account.');
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        User $agent,
        SupportQueueService $queue,
    ): RedirectResponse {
        $this->authorize('manageAgents', $workspace);

        abort_unless(
            $agent->isAgent()
            && $agent->workspace_id === $workspace->id
            && $agent->company_id === $request->user()->company_id,
            404
        );

        $workspace->conversations()
            ->where('agent_id', $agent->id)
            ->where('status', 'assigned')
            ->update(['agent_id' => null, 'status' => 'waiting']);

        $agent->delete();
        $queue->dispatchWaiting($workspace);

        return back()->with('status', 'Agent removed. Their email can now be invited by another company.');
    }
}
