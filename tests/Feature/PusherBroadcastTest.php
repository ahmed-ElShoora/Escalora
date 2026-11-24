<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PusherBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_authenticate_only_their_private_conversation_channel(): void
    {
        Config::set('broadcasting.connections.pusher.key', 'test-key');
        Config::set('broadcasting.connections.pusher.secret', 'test-secret');
        Config::set('broadcasting.connections.pusher.app_id', '123456');
        Config::set('broadcasting.connections.pusher.options.cluster', 'mt1');

        $visitorToken = str_repeat('v', 64);
        $conversation = SupportConversation::factory()->create([
            'visitor_token_hash' => hash('sha256', $visitorToken),
        ]);

        $this->postJson(route('support.broadcasting.auth'), [
            'socket_id' => '123.456',
            'channel_name' => 'private-support-conversation.'.$conversation->id,
            'visitor_token' => $visitorToken,
        ])->assertOk()->assertJsonStructure(['auth']);

        $this->postJson(route('support.broadcasting.auth'), [
            'socket_id' => '123.456',
            'channel_name' => 'private-support-conversation.'.$conversation->id,
            'visitor_token' => str_repeat('x', 64),
        ])->assertForbidden();
    }

    public function test_only_an_agent_in_the_workspace_can_authenticate_its_private_channel(): void
    {
        Config::set('broadcasting.connections.pusher.key', 'test-key');
        Config::set('broadcasting.connections.pusher.secret', 'test-secret');
        Config::set('broadcasting.connections.pusher.app_id', '123456');
        Config::set('broadcasting.connections.pusher.options.cluster', 'mt1');

        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create();

        $this->actingAs($agent)
            ->postJson(route('support.broadcasting.auth'), [
                'socket_id' => '123.456',
                'channel_name' => 'private-support-workspace.'.$workspace->id,
            ])->assertOk()->assertJsonStructure(['auth']);

        $otherWorkspace = Workspace::factory()->create();
        $this->actingAs($agent)
            ->postJson(route('support.broadcasting.auth'), [
                'socket_id' => '123.456',
                'channel_name' => 'private-support-workspace.'.$otherWorkspace->id,
            ])->assertForbidden();
    }

    public function test_agent_can_authenticate_only_conversations_assigned_to_them(): void
    {
        Config::set('broadcasting.connections.pusher.key', 'test-key');
        Config::set('broadcasting.connections.pusher.secret', 'test-secret');
        Config::set('broadcasting.connections.pusher.app_id', '123456');
        Config::set('broadcasting.connections.pusher.options.cluster', 'mt1');

        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create();
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($agent)
            ->postJson(route('support.broadcasting.auth'), [
                'socket_id' => '123.456',
                'channel_name' => 'private-support-conversation.'.$conversation->id,
            ])->assertOk()->assertJsonStructure(['auth']);

        $otherAgent = User::factory()->agent($workspace)->create();

        $this->actingAs($otherAgent)
            ->postJson(route('support.broadcasting.auth'), [
                'socket_id' => '123.456',
                'channel_name' => 'private-support-conversation.'.$conversation->id,
            ])->assertForbidden();
    }

    public function test_private_channel_authentication_fails_if_pusher_is_not_configured(): void
    {
        Config::set('broadcasting.connections.pusher.key', null);

        $conversation = SupportConversation::factory()->create();

        $this->postJson(route('support.broadcasting.auth'), [
            'socket_id' => '123.456',
            'channel_name' => 'private-support-conversation.'.$conversation->id,
        ])->assertServiceUnavailable();
    }
}
