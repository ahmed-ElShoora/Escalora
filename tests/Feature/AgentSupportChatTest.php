<?php

namespace Tests\Feature;

use App\Events\SupportConversationUpdated;
use App\Events\SupportMessageSent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AgentSupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_replies_closes_chat_and_customer_can_rate_once(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create();
        $visitorToken = 'visitor-token-for-agent-chat';
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'visitor_token_hash' => hash('sha256', $visitorToken),
            'status' => 'assigned',
        ]);
        $conversation->messages()->create([
            'sender' => 'customer',
            'body' => 'I need to update my billing details.',
        ]);
        Event::fake([SupportMessageSent::class]);

        $this->actingAs($agent)
            ->postJson(route('agent.conversations.send', $conversation), [
                'message' => 'I can help you update those details.',
            ])
            ->assertOk()
            ->assertJsonPath('sent', true)
            ->assertJsonPath('message.sender', 'agent')
            ->assertJsonPath('message.body', 'I can help you update those details.');

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'sender_id' => $agent->id,
            'sender' => 'agent',
            'body' => 'I can help you update those details.',
        ]);
        Event::assertDispatched(SupportMessageSent::class, fn (SupportMessageSent $event): bool => $event->message->sender === 'agent'
            && $event->message->support_conversation_id === $conversation->id
        );

        $this->actingAs($agent)
            ->postJson(route('agent.conversations.close', $conversation))
            ->assertOk();
        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'status' => 'closed',
        ]);

        $this->postJson(route('support.conversations.rating', $conversation), [
            'visitor_token' => $visitorToken,
            'rating' => 5,
        ])->assertOk()->assertJsonPath('rating', 5);

        $this->postJson(route('support.conversations.rating', $conversation), [
            'visitor_token' => $visitorToken,
            'rating' => 4,
        ])->assertStatus(409);

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'rating' => 5,
        ]);
    }

    public function test_agent_cannot_read_a_conversation_from_another_workspace(): void
    {
        $agent = User::factory()->agent()->create();
        $otherWorkspace = Workspace::factory()->create();
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'agent_id' => User::factory()->agent($otherWorkspace)->create()->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($agent)
            ->getJson(route('agent.conversations.messages', $conversation))
            ->assertNotFound();
    }

    public function test_agent_message_history_masks_legacy_customer_phone_and_card_numbers(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create();
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
        $conversation->messages()->create([
            'sender' => 'customer',
            'body' => 'Phone +1 (415) 555-2671, card 4111 1111 1111 1111.',
        ]);

        $this->actingAs($agent)
            ->getJson(route('agent.conversations.messages', $conversation))
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Phone ********, card ********.');
    }

    public function test_agent_message_succeeds_when_pusher_is_temporarily_unavailable(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create();
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
        $this->mock(BroadcastFactory::class)
            ->shouldReceive('queue')
            ->once()
            ->andThrow(new BroadcastException('Pusher is temporarily unavailable.'));

        $this->actingAs($agent)
            ->postJson(route('agent.conversations.send', $conversation), [
                'message' => 'I am checking that for you.',
            ])
            ->assertOk()
            ->assertJsonPath('sent', true);

        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $conversation->id,
            'sender_id' => $agent->id,
            'body' => 'I am checking that for you.',
        ]);
    }

    public function test_agent_can_send_multiple_messages_without_closing_the_selected_chat(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create();
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
        Event::fake([SupportMessageSent::class]);

        $firstResponse = $this->actingAs($agent)
            ->postJson(route('agent.conversations.send', $conversation), [
                'message' => 'I received your request.',
            ]);
        $firstResponse->assertOk()->assertJsonPath('message.body', 'I received your request.');

        $secondResponse = $this->postJson(route('agent.conversations.send', $conversation), [
            'message' => 'Can I help with anything else?',
        ]);
        $secondResponse->assertOk()->assertJsonPath('message.body', 'Can I help with anything else?');

        $this->assertSame('assigned', $conversation->fresh()->status);
        $this->assertSame(
            2,
            $conversation->messages()->where('sender', 'agent')->count(),
        );
    }

    public function test_agent_remains_online_during_a_temporarily_delayed_heartbeat(): void
    {
        $this->travelTo(now()->subMinutes(2));
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create([
            'is_available' => true,
            'last_seen_at' => now(),
        ]);
        $this->travelTo(now()->addMinutes(2));

        $this->assertTrue($agent->fresh()->isOnline());
    }

    public function test_agent_coming_online_receives_the_oldest_waiting_chat(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create([
            'is_available' => false,
        ]);
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'waiting',
            'escalated_at' => now(),
        ]);
        SupportMessage::factory()->create([
            'support_conversation_id' => $conversation->id,
        ]);
        Event::fake([SupportConversationUpdated::class]);

        $this->actingAs($agent)
            ->putJson(route('agent.presence.update'), ['is_available' => true])
            ->assertOk()
            ->assertJsonPath('is_available', true);

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
        Event::assertDispatched(SupportConversationUpdated::class, fn (SupportConversationUpdated $event): bool => $event->conversation->is($conversation->fresh())
            && $event->conversation->agent_id === $agent->id
        );
    }

    public function test_closing_a_chat_removes_it_from_the_queue_and_assigns_the_oldest_waiting_chat(): void
    {
        $workspace = Workspace::factory()->create();
        $agent = User::factory()->agent($workspace)->create([
            'is_available' => true,
            'last_seen_at' => now(),
        ]);
        $activeConversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
        $waitingConversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'waiting',
        ]);
        Event::fake([SupportConversationUpdated::class]);

        $this->actingAs($agent)
            ->postJson(route('agent.conversations.close', $activeConversation))
            ->assertOk();

        $this->assertDatabaseHas('support_conversations', [
            'id' => $activeConversation->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('support_conversations', [
            'id' => $waitingConversation->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($agent)
            ->getJson(route('agent.conversations.index'))
            ->assertOk()
            ->assertJsonPath('waiting_count', 0)
            ->assertJsonPath('conversations.0.id', $waitingConversation->id);
    }
}
