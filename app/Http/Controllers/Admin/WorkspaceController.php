<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWorkspaceRequest;
use App\Http\Requests\Admin\UpdateWorkspaceRequest;
use App\Models\SupportConversation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Workspace::class);

        $workspaces = Workspace::query()
            ->where('company_id', $request->user()->company_id)
            ->withCount('agents')
            ->latest()
            ->paginate(10);

        return view('admin.workspaces.index', compact('workspaces'));
    }

    public function create(): View
    {
        $this->authorize('create', Workspace::class);

        return view('admin.workspaces.create');
    }

    public function store(StoreWorkspaceRequest $request): RedirectResponse
    {
        $workspace = Workspace::query()->create([
            ...$request->validated(),
            'company_id' => $request->user()->company_id,
        ]);

        return redirect()
            ->route('admin.workspaces.show', $workspace)
            ->with('status', 'Workspace created successfully.');
    }

    public function show(Request $request, Workspace $workspace): View
    {
        $this->authorize('view', $workspace);

        $period = $request->query('period', 'week');
        $periods = [
            'day' => ['label' => 'Last 24 hours', 'start' => now()->subDay()],
            'week' => ['label' => 'Last 7 days', 'start' => now()->subWeek()],
            'month' => ['label' => 'Last 30 days', 'start' => now()->subMonth()],
            'year' => ['label' => 'Last 12 months', 'start' => now()->subYear()],
        ];

        abort_unless(is_string($period) && array_key_exists($period, $periods), 404);
        $periodStart = $periods[$period]['start'];

        $workspace->load([
            'agents' => fn ($query) => $query
                ->withCount([
                    'supportConversations as period_chats_count' => fn ($conversations) => $conversations
                        ->where('created_at', '>=', $periodStart),
                    'supportConversations as active_chats_count' => fn ($conversations) => $conversations
                        ->where('status', 'assigned'),
                ])
                ->withAvg([
                    'supportConversations as period_rating' => fn ($conversations) => $conversations
                        ->whereNotNull('rating')
                        ->where('rated_at', '>=', $periodStart),
                ], 'rating')
                ->latest(),
        ]);

        $periodConversations = SupportConversation::query()
            ->where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $periodStart);
        $analytics = [
            'chats' => (clone $periodConversations)->count(),
            'closed' => (clone $periodConversations)->where('status', 'closed')->count(),
            'rating' => SupportConversation::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('rating')
                ->where('rated_at', '>=', $periodStart)
                ->avg('rating'),
            'waiting' => $workspace->conversations()->where('status', 'waiting')->count(),
        ];

        return view('admin.workspaces.show', [
            'workspace' => $workspace,
            'period' => $period,
            'periods' => $periods,
            'analytics' => $analytics,
        ]);
    }

    public function edit(Workspace $workspace): View
    {
        $this->authorize('update', $workspace);

        return view('admin.workspaces.edit', compact('workspace'));
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->update($request->validated());

        return redirect()
            ->route('admin.workspaces.show', $workspace)
            ->with('status', 'Workspace updated successfully.');
    }

    public function destroy(Workspace $workspace): RedirectResponse
    {
        $this->authorize('delete', $workspace);

        $workspace->agents()->delete();
        $workspace->delete();

        return redirect()
            ->route('admin.workspaces.index')
            ->with('status', 'Workspace and its agents were removed.');
    }
}
