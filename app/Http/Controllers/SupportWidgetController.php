<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\Workspace;
use App\Services\OpenRouterChatService;
use App\Services\SupportBroadcastService;
use App\Services\SupportQueueService;
use App\Services\SupportSensitiveDataRedactor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class SupportWidgetController extends Controller
{
    public function __construct(private SupportBroadcastService $broadcasts) {}

    public function show(Request $request, Workspace $workspace): View
    {
        return view('support.chat', [
            'workspace' => $workspace,
            'embedded' => $request->routeIs('support.embed'),
        ]);
    }

    public function store(
        Request $request,
        Workspace $workspace,
        OpenRouterChatService $assistant,
        SupportQueueService $queue,
        SupportSensitiveDataRedactor $redactor,
    ): JsonResponse {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);
        $message = $redactor->redact($validated['message']);

        $visitorToken = Str::random(64);
        $conversation = $workspace->conversations()->create([
            'visitor_token_hash' => hash('sha256', $visitorToken),
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'status' => 'ai',
        ]);

        $customerMessage = $conversation->messages()->create([
            'sender' => 'customer',
            'body' => $message,
        ]);
        $this->broadcasts->message($customerMessage);

        return $this->respondToCustomer(
            $workspace,
            $conversation,
            $message,
            $visitorToken,
            $assistant,
            $queue,
        );
    }

    public function messages(
        Request $request,
        SupportConversation $conversation,
        SupportQueueService $queue,
        SupportSensitiveDataRedactor $redactor,
    ): JsonResponse {
        $this->authorizeVisitor($request, $conversation);

        $conversation->load('agent:id,name');

        if (
            $conversation->status === 'assigned'
            && ($conversation->agent === null || ! $conversation->agent->isOnline())
        ) {
            $queue->dispatchWaiting($conversation->workspace);
            $conversation->refresh()->load('agent:id,name');
        }

        return response()->json([
            ...$this->conversationState($conversation, $queue),
            'messages' => $conversation->messages()
                ->with('senderUser:id,name')
                ->where('id', '>', $request->integer('after_id'))
                ->oldest('id')
                ->get(['id', 'sender', 'sender_id', 'body', 'created_at'])
                ->map(fn (SupportMessage $message): array => [
                    'id' => $message->id,
                    'sender' => $message->sender,
                    'sender_name' => $message->sender === 'agent'
                        ? ($message->senderUser?->name ?? 'Support agent')
                        : null,
                    'body' => $redactor->redact($message->body),
                    'created_at' => $message->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function send(
        Request $request,
        SupportConversation $conversation,
        OpenRouterChatService $assistant,
        SupportQueueService $queue,
        SupportSensitiveDataRedactor $redactor,
    ): JsonResponse {
        $this->authorizeVisitor($request, $conversation);

        if ($conversation->status === 'closed') {
            return response()->json(['message' => 'This conversation is closed.'], 409);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);
        $message = $redactor->redact($validated['message']);

        $customerMessage = $conversation->messages()->create([
            'sender' => 'customer',
            'body' => $message,
        ]);
        $this->broadcasts->message($customerMessage);

        return $this->respondToCustomer(
            $conversation->workspace,
            $conversation->refresh(),
            $message,
            $request->string('visitor_token')->toString(),
            $assistant,
            $queue,
        );
    }

    public function rate(
        Request $request,
        SupportConversation $conversation,
    ): JsonResponse {
        $this->authorizeVisitor($request, $conversation);

        if ($conversation->status !== 'closed' || $conversation->agent_id === null) {
            return response()->json(['message' => 'Only closed agent conversations can be rated.'], 409);
        }

        if ($conversation->rated_at !== null) {
            return response()->json(['message' => 'This conversation has already been rated.'], 409);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        $conversation->update([
            'rating' => (int) $validated['rating'],
            'rated_at' => now(),
        ]);

        $this->broadcasts->conversation($conversation->refresh());

        return response()->json(['rating' => $conversation->rating]);
    }

    public function end(
        Request $request,
        SupportConversation $conversation,
        SupportQueueService $queue,
    ): JsonResponse {
        $this->authorizeVisitor($request, $conversation);

        if ($conversation->status !== 'closed') {
            DB::transaction(function () use ($conversation): void {
                $conversation->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);
            });

            $this->broadcasts->conversation($conversation->refresh());
            $queue->dispatchWaiting($conversation->workspace);
        }

        return response()->json(['closed' => true]);
    }

    private function respondToCustomer(
        Workspace $workspace,
        SupportConversation $conversation,
        string $message,
        string $visitorToken,
        OpenRouterChatService $assistant,
        SupportQueueService $queue,
    ): JsonResponse {
        if ($conversation->status === 'ai') {
            $shouldEscalate = false;
            $reply = null;

            try {
                $shouldEscalate = $assistant->shouldEscalate($workspace, $message);

                if (! $shouldEscalate) {
                    $reply = $assistant->reply($workspace, $conversation);
                }
            } catch (ConnectionException|RequestException|RuntimeException $exception) {
                Log::warning('Support assistant could not answer a customer.', [
                    'workspace_id' => $workspace->id,
                    'conversation_id' => $conversation->id,
                    'reason' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Your message was saved, but the AI assistant is temporarily unavailable. Please try again shortly.',
                    'conversation_id' => $conversation->id,
                    'visitor_token' => $visitorToken,
                ], 503);
            }

            if ($shouldEscalate) {
                $conversation->update([
                    'status' => 'waiting',
                    'escalated_at' => now(),
                ]);
                $systemMessage = $conversation->messages()->create([
                    'sender' => 'system',
                    'body' => 'We are connecting you with a human support agent.',
                ]);
                $this->broadcasts->message($systemMessage);
                $queue->dispatchWaiting($workspace);
                $conversation->refresh()->load('agent:id,name');

                if ($conversation->status === 'waiting') {
                    $this->broadcasts->conversation($conversation);
                }
            } elseif ($reply !== null) {
                $assistantMessage = $conversation->messages()->create([
                    'sender' => 'assistant',
                    'body' => $reply,
                ]);
                $this->broadcasts->message($assistantMessage);
            }
        }

        return response()->json([
            ...$this->conversationState($conversation->refresh(), $queue),
            'conversation_id' => $conversation->id,
            'visitor_token' => $visitorToken,
        ]);
    }

    private function conversationState(
        SupportConversation $conversation,
        SupportQueueService $queue,
    ): array {
        return [
            'status' => $conversation->status,
            'queue_position' => $queue->position($conversation),
            'agent_name' => $conversation->agent?->name,
            'rating' => $conversation->rating,
            'rated' => $conversation->rated_at !== null,
        ];
    }

    private function authorizeVisitor(Request $request, SupportConversation $conversation): void
    {
        $token = $request->input('visitor_token');

        abort_unless(
            is_string($token)
                && hash_equals($conversation->visitor_token_hash, hash('sha256', $token)),
            404,
        );
    }
}
