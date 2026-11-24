<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\SupportBroadcastService;
use App\Services\SupportQueueService;
use App\Services\SupportSensitiveDataRedactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function __construct(private SupportBroadcastService $broadcasts) {}

    public function index(Request $request): JsonResponse
    {
        $agent = $request->user();
        $workspace = $agent->workspace;

        if ($workspace === null) {
            return response()->json(['conversations' => [], 'waiting_count' => 0]);
        }

        $conversations = SupportConversation::query()
            ->where('workspace_id', $workspace->id)
            ->where('agent_id', $agent->id)
            ->where('status', 'assigned')
            ->latest('updated_at')
            ->get(['id', 'customer_name', 'created_at', 'updated_at']);

        return response()->json([
            'conversations' => $conversations,
            'waiting_count' => $workspace->conversations()->where('status', 'waiting')->count(),
            'online_agents' => $workspace->agents()
                ->where('is_available', true)
                ->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_TIMEOUT_SECONDS))
                ->count(),
        ]);
    }

    public function presence(Request $request, SupportQueueService $queue): JsonResponse
    {
        $validated = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        $agent = $request->user();
        $agent->forceFill([
            'is_available' => (bool) $validated['is_available'],
            'last_seen_at' => now(),
        ])->save();

        if ($agent->workspace !== null) {
            $queue->dispatchWaiting($agent->workspace);
        }

        return response()->json(['is_available' => $agent->is_available]);
    }

    public function messages(
        Request $request,
        SupportConversation $conversation,
        SupportSensitiveDataRedactor $redactor,
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation);

        return response()->json([
            'messages' => $conversation->messages()
                ->with('senderUser:id,name')
                ->where('id', '>', $request->integer('after_id'))
                ->oldest('id')
                ->get(['id', 'sender', 'sender_id', 'body', 'created_at'])
                ->map(fn (SupportMessage $message): array => [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'sender_name' => match ($message->sender) {
                        'agent' => $message->senderUser?->name ?? 'Support agent',
                        'customer' => $conversation->customer_name,
                        default => null,
                    },
                    'body' => $redactor->redact($message->body),
                    'created_at' => $message->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function send(Request $request, SupportConversation $conversation): JsonResponse
    {
        $this->authorizeConversation($request, $conversation);

        if ($conversation->status !== 'assigned') {
            return response()->json(['message' => 'This conversation is no longer active.'], 409);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $agentMessage = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'sender' => 'agent',
            'body' => $validated['message'],
        ]);
        $this->broadcasts->message($agentMessage);

        return response()->json([
            'sent' => true,
            'message' => [
                'id' => $agentMessage->id,
                'conversation_id' => $conversation->id,
                'sender' => 'agent',
                'sender_name' => $request->user()->name,
                'body' => $agentMessage->body,
                'created_at' => $agentMessage->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function close(
        Request $request,
        SupportConversation $conversation,
        SupportQueueService $queue,
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation);

        if ($conversation->status !== 'assigned') {
            return response()->json(['message' => 'This conversation is no longer active.'], 409);
        }

        DB::transaction(function () use ($conversation): void {
            $conversation->update([
                'status' => 'closed',
                'closed_at' => now(),
            ]);
        });

        $this->broadcasts->conversation($conversation->refresh());
        $queue->dispatchWaiting($conversation->workspace);

        return response()->json(['closed' => true]);
    }

    private function authorizeConversation(Request $request, SupportConversation $conversation): void
    {
        abort_unless(
            $conversation->workspace_id === $request->user()->workspace_id
                && $conversation->agent_id === $request->user()->id,
            404,
        );
    }
}
