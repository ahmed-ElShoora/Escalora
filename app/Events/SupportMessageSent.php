<?php

namespace App\Events;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SupportMessage $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support-conversation.'.$this->message->support_conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing('senderUser:id,name', 'conversation:id,customer_name');

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->support_conversation_id,
            'sender' => $this->message->sender,
            'sender_name' => match ($this->message->sender) {
                'agent' => $this->message->senderUser?->name ?? 'Support agent',
                'customer' => $this->message->conversation?->customer_name,
                'assistant' => 'AI assistant',
                default => 'Support',
            },
            'body' => $this->message->body,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
