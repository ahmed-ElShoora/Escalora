<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $agent = $request->user()->load(['workspace', 'company']);
        $conversations = $agent->workspace === null
            ? collect()
            : SupportConversation::query()
                ->where('workspace_id', $agent->workspace->id)
                ->where('agent_id', $agent->id)
                ->where('status', 'assigned')
                ->latest('updated_at')
                ->get(['id', 'customer_name', 'created_at', 'updated_at']);

        return view('agent.dashboard', [
            'agent' => $agent,
            'workspace' => $agent->workspace,
            'company' => $agent->company,
            'conversations' => $conversations,
            'waitingCount' => $agent->workspace?->conversations()->where('status', 'waiting')->count() ?? 0,
        ]);
    }
}
