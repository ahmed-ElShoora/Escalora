<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\SupportQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceSupportController extends Controller
{
    public function presence(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $agents = $workspace->agents()
            ->latest('name')
            ->get(['id', 'name', 'is_available', 'last_seen_at'])
            ->map(fn ($agent): array => [
                'id' => $agent->id,
                'online' => $agent->isOnline(),
                'availability' => $agent->isOnline()
                    ? 'Online'
                    : ($agent->is_available ? 'Away' : 'Offline'),
                'last_seen' => $agent->last_seen_at?->diffForHumans() ?? 'No activity yet',
            ]);

        return response()->json([
            'online_count' => $agents->where('online', true)->count(),
            'agents' => $agents->values(),
        ]);
    }

    public function update(Request $request, Workspace $workspace, SupportQueueService $queue): RedirectResponse
    {
        $this->authorize('update', $workspace);

        $validated = $request->validate([
            'escalation_keywords' => ['nullable', 'string', 'max:4000'],
            'max_active_chats' => ['required', 'integer', 'between:1,30'],
        ]);

        $keywords = collect(preg_split('/\R/u', $validated['escalation_keywords'] ?? '') ?: [])
            ->map(fn (string $keyword): string => Str::limit(trim($keyword), 100, ''))
            ->filter()
            ->unique(fn (string $keyword): string => Str::lower($keyword))
            ->values()
            ->all();

        $workspace->update([
            'escalation_keywords' => $keywords,
            'max_active_chats' => (int) $validated['max_active_chats'],
        ]);

        $queue->dispatchWaiting($workspace->refresh());

        return back()->with('status', 'Support routing settings updated.');
    }
}
