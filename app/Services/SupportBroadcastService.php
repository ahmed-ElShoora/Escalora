<?php

namespace App\Services;

use App\Events\SupportConversationUpdated;
use App\Events\SupportMessageSent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;

class SupportBroadcastService
{
    public function message(SupportMessage $message): void
    {
        try {
            SupportMessageSent::dispatch($message);
        } catch (BroadcastException|GuzzleException $exception) {
            Log::warning('A support message was saved, but its real-time broadcast failed.', [
                'conversation_id' => $message->support_conversation_id,
                'message_id' => $message->id,
                'reason' => $exception->getMessage(),
            ]);
        }
    }

    public function conversation(SupportConversation $conversation): void
    {
        try {
            SupportConversationUpdated::dispatch($conversation);
        } catch (BroadcastException|GuzzleException $exception) {
            Log::warning('A support conversation was updated, but its real-time broadcast failed.', [
                'conversation_id' => $conversation->id,
                'reason' => $exception->getMessage(),
            ]);
        }
    }
}
