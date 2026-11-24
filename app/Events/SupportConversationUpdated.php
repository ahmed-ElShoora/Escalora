<?php

namespace App\Events;

use App\Models\SupportConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportConversation $conversation) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support-conversation.'.$this->conversation->id),
            new PrivateChannel('support-workspace.'.$this->conversation->workspace_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.conversation.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->conversation->id,
            'workspace_id' => $this->conversation->workspace_id,
            'agent_id' => $this->conversation->agent_id,
            'status' => $this->conversation->status,
            'queue_position' => $this->conversation->status === 'waiting'
                ? $this->conversation->workspace->conversations()
                    ->where('status', 'waiting')
                    ->where(function ($query): void {
                        $query->where('created_at', '<', $this->conversation->created_at)
                            ->orWhere(function ($query): void {
                                $query->where('created_at', $this->conversation->created_at)
                                    ->where('id', '<=', $this->conversation->id);
                            });
                    })
                    ->count()
                : null,
            'agent_name' => $this->conversation->agent?->name,
            'rating' => $this->conversation->rating,
            'rated' => $this->conversation->rated_at !== null,
        ];
    }
}
