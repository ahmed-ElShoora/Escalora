<?php

namespace App\Services;

use App\Models\SupportConversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class SupportQueueService
{
    public function __construct(private SupportBroadcastService $broadcasts) {}

    public function dispatchWaiting(Workspace $workspace): void
    {
        $updatedConversationIds = [];

        DB::transaction(function () use ($workspace, &$updatedConversationIds): void {
            $releasedConversations = SupportConversation::query()
                ->where('workspace_id', $workspace->id)
                ->where('status', 'assigned')
                ->whereHas('agent', function ($query): void {
                    $query->where('is_available', false)
                        ->orWhereNull('last_seen_at')
                        ->orWhere('last_seen_at', '<', now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS));
                })
                ->get();

            $releasedConversations->each(function (SupportConversation $conversation): void {
                $conversation->update([
                    'agent_id' => null,
                    'status' => 'waiting',
                ]);
            });
            $updatedConversationIds = $releasedConversations->modelKeys();

            $availableAgents = User::query()
                ->where('workspace_id', $workspace->id)
                ->where('role', 'agent')
                ->where('status', 'active')
                ->where('is_available', true)
                ->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $agentLoads = [];

            foreach ($availableAgents as $agent) {
                $agentLoads[$agent->id] = SupportConversation::query()
                    ->where('agent_id', $agent->id)
                    ->where('status', 'assigned')
                    ->count();
            }

            $assignedConversations = [];

            while ($agentLoads !== []) {
                $eligibleLoads = collect($agentLoads)
                    ->filter(fn (int $load): bool => $load < $workspace->max_active_chats);

                if ($eligibleLoads->isEmpty()) {
                    break;
                }

                $agentId = $eligibleLoads->sort()->keys()->first();
                $conversation = SupportConversation::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('status', 'waiting')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($conversation === null) {
                    break;
                }

                $conversation->update([
                    'agent_id' => $agentId,
                    'status' => 'assigned',
                ]);
                $updatedConversationIds[] = $conversation->id;
                $agentLoads[$agentId]++;
            }
        });

        if ($updatedConversationIds !== []) {
            SupportConversation::query()
                ->with('agent:id,name')
                ->whereIn('id', array_unique($updatedConversationIds))
                ->get()
                ->each(fn (SupportConversation $conversation) => $this->broadcasts->conversation($conversation));
        }
    }

    public function position(SupportConversation $conversation): ?int
    {
        if ($conversation->status !== 'waiting') {
            return null;
        }

        return SupportConversation::query()
            ->where('workspace_id', $conversation->workspace_id)
            ->where('status', 'waiting')
            ->where(function ($query) use ($conversation): void {
                $query->where('created_at', '<', $conversation->created_at)
                    ->orWhere(function ($query) use ($conversation): void {
                        $query->where('created_at', $conversation->created_at)
                            ->where('id', '<=', $conversation->id);
                    });
            })
            ->count();
    }
}
