<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $admin = $request->user();
        $companyId = $admin?->company_id;

        $workspaceCount = Workspace::query()->where('company_id', $companyId)->count();
        $agentCount = User::query()
            ->where('company_id', $companyId)
            ->where('role', UserRole::Agent)
            ->count();
        $pendingInvites = User::query()
            ->where('company_id', $companyId)
            ->where('role', UserRole::Agent)
            ->where('status', UserStatus::Pending)
            ->count();
        $activeAgents = User::query()
            ->where('company_id', $companyId)
            ->where('role', UserRole::Agent)
            ->where('status', UserStatus::Active)
            ->count();

        $recentWorkspaces = Workspace::query()
            ->where('company_id', $companyId)
            ->withCount('agents')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'admin' => $admin,
            'workspaceCount' => $workspaceCount,
            'agentCount' => $agentCount,
            'pendingInvites' => $pendingInvites,
            'activeAgents' => $activeAgents,
            'recentWorkspaces' => $recentWorkspaces,
        ]);
    }
}
