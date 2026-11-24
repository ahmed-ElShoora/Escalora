<?php

namespace App\Http\Controllers;

use App\Models\SupportConversation;
use Illuminate\Broadcasting\Broadcasters\PusherBroadcaster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Validation\ValidationException;

class SupportBroadcastAuthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string', 'max:200'],
            'visitor_token' => ['nullable', 'string', 'size:64'],
        ]);

        if (
            ! filled(config('broadcasting.connections.pusher.key'))
            || ! filled(config('broadcasting.connections.pusher.secret'))
            || ! filled(config('broadcasting.connections.pusher.app_id'))
        ) {
            return response()->json(['message' => 'Pusher is not configured.'], 503);
        }

        $channel = $validated['channel_name'];

        if (preg_match('/^private-support-conversation\.(\d+)$/', $channel, $matches) === 1) {
            $conversation = SupportConversation::query()
                ->with('workspace:id')
                ->find($matches[1]);

            if ($conversation === null || ! $this->canAccessConversation($request, $conversation, $validated['visitor_token'] ?? null)) {
                abort(403);
            }
        } elseif (preg_match('/^private-support-workspace\.(\d+)$/', $channel, $matches) === 1) {
            $agent = $request->user();

            abort_unless(
                $agent?->isAgent()
                    && $agent->workspace_id === (int) $matches[1]
                    && $agent->isActive(),
                403,
            );
        } else {
            throw ValidationException::withMessages([
                'channel_name' => 'This channel is not available.',
            ]);
        }

        $broadcaster = Broadcast::connection('pusher');

        abort_unless($broadcaster instanceof PusherBroadcaster, 503);

        return response()->json(
            $broadcaster->validAuthenticationResponse($request, true),
        );
    }

    private function canAccessConversation(
        Request $request,
        SupportConversation $conversation,
        ?string $visitorToken,
    ): bool {
        $user = $request->user();

        if (
            $user?->isAgent()
            && $user->isActive()
            && $conversation->agent_id === $user->id
            && $user->workspace_id === $conversation->workspace_id
        ) {
            return true;
        }

        return $visitorToken !== null
            && hash_equals($conversation->visitor_token_hash, hash('sha256', $visitorToken));
    }
}
