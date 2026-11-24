<?php

namespace Tests\Feature;

use App\Events\SupportMessageSent;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupportChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_gets_an_ai_reply_using_the_workspace_description(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Your plan includes priority email support.']],
                ],
            ]),
        ]);
        Config::set('services.openrouter.api_key', 'test-openrouter-key');
        Config::set('services.openrouter.model', 'openai/gpt-4o-mini');

        $workspace = Workspace::factory()->create([
            'name' => 'Billing Desk',
            'description' => 'Customers can cancel their monthly plan at any time.',
        ]);
        Event::fake([SupportMessageSent::class]);

        $response = $this->postJson(route('support.conversations.store', $workspace->public_token), [
            'customer_name' => 'Jamie Customer',
            'customer_email' => 'jamie@example.com',
            'message' => 'What support is included?',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ai');
        $this->assertDatabaseHas('support_messages', [
            'support_conversation_id' => $response->json('conversation_id'),
            'sender' => 'assistant',
            'body' => 'Your plan includes priority email support.',
        ]);

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-openrouter-key')
            && str_contains($request->data()['messages'][0]['content'], 'Customers can cancel their monthly plan')
            && $request->data()['model'] === 'openai/gpt-4o-mini'
        );
        Event::assertDispatched(SupportMessageSent::class, fn (SupportMessageSent $event): bool => $event->message->sender === 'assistant'
            && $event->message->body === 'Your plan includes priority email support.'
        );
    }

    public function test_customer_intent_matching_escalates_semantically_and_redacts_sensitive_numbers(): void
    {
        Http::preventStrayRequests();
        Config::set('services.openrouter.api_key', 'test-openrouter-key');
        Config::set('services.openrouter.model', 'openai/gpt-4o-mini');
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => function () {
                return Http::response([
                    'choices' => [
                        ['message' => ['content' => '{"escalate":true}']],
                    ],
                ]);
            },
        ]);

        $workspace = Workspace::factory()->create([
            'escalation_keywords' => ['cancel my subscription'],
        ]);
        Event::fake([SupportMessageSent::class]);

        $response = $this->postJson(route('support.conversations.store', $workspace->public_token), [
            'customer_name' => 'Alex Customer',
            'message' => 'I want to stop paying. Call +1 (415) 555-2671. Card: 4111 1111 1111 1111.',
        ]);

        $response->assertOk()->assertJsonPath('status', 'waiting');
        $savedMessage = SupportMessage::query()
            ->where('sender', 'customer')
            ->firstOrFail();
        $this->assertSame(
            'I want to stop paying. Call ********. Card: ********.',
            $savedMessage->body,
        );
        $this->assertStringNotContainsString('415', $savedMessage->body);
        $this->assertStringNotContainsString('4111', $savedMessage->body);
        Http::assertSent(fn (ClientRequest $request): bool => str_contains(
            $request->data()['messages'][1]['content'],
            'I want to stop paying. Call ********. Card: ********.',
        ));
        Event::assertDispatched(SupportMessageSent::class, fn (SupportMessageSent $event): bool => $event->message->sender === 'customer'
            && $event->message->body === $savedMessage->body
        );
    }

    public function test_customer_without_matching_escalation_intent_receives_an_ai_reply(): void
    {
        Http::preventStrayRequests();
        Config::set('services.openrouter.api_key', 'test-openrouter-key');
        Config::set('services.openrouter.model', 'openai/gpt-4o-mini');
        $requestCount = 0;
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => function (ClientRequest $request) use (&$requestCount) {
                $requestCount++;

                return Http::response([
                    'choices' => [
                        ['message' => ['content' => $requestCount === 1
                            ? '{"escalate":false}'
                            : 'Your account remains active.']],
                    ],
                ]);
            },
        ]);

        $workspace = Workspace::factory()->create([
            'escalation_keywords' => ['cancel my subscription'],
        ]);

        $this->postJson(route('support.conversations.store', $workspace->public_token), [
            'customer_name' => 'Jordan Customer',
            'message' => 'How do I update my billing address?',
        ])->assertOk()->assertJsonPath('status', 'ai');

        $this->assertSame(2, $requestCount);
        $this->assertDatabaseHas('support_messages', [
            'sender' => 'assistant',
            'body' => 'Your account remains active.',
        ]);
    }

    public function test_openrouter_escalation_check_failure_is_reported_without_losing_the_redacted_message(): void
    {
        Http::preventStrayRequests();
        Config::set('services.openrouter.api_key', 'test-openrouter-key');
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([], 503),
        ]);
        $workspace = Workspace::factory()->create([
            'escalation_keywords' => ['cancel my subscription'],
        ]);

        $this->postJson(route('support.conversations.store', $workspace->public_token), [
            'customer_name' => 'Riley Customer',
            'message' => 'Please help with my card 4111 1111 1111 1111.',
        ])->assertServiceUnavailable();

        $this->assertDatabaseHas('support_messages', [
            'sender' => 'customer',
            'body' => 'Please help with my card ********.',
        ]);
    }

    public function test_customer_can_open_the_shared_page_and_embedded_chat(): void
    {
        $workspace = Workspace::factory()->create(['name' => 'Returns Desk']);

        $this->get(route('support.show', $workspace->public_token))
            ->assertOk()
            ->assertSee('Returns Desk')
            ->assertSee(route('support.conversations.store', $workspace->public_token));

        $this->get(route('support.embed', $workspace->public_token))
            ->assertOk()
            ->assertSee('data-support-widget', false);
    }

    public function test_customer_message_is_saved_and_ai_failure_is_reported(): void
    {
        Http::preventStrayRequests();
        Config::set('services.openrouter.api_key', null);

        $workspace = Workspace::factory()->create();

        $response = $this->postJson(route('support.conversations.store', $workspace->public_token), [
            'customer_name' => 'Taylor Customer',
            'message' => 'I need help with my account.',
        ]);

        $response->assertServiceUnavailable()
            ->assertJsonPath('message', 'Your message was saved, but the AI assistant is temporarily unavailable. Please try again shortly.');
        $this->assertDatabaseHas('support_messages', [
            'sender' => 'customer',
            'body' => 'I need help with my account.',
        ]);
        $this->assertDatabaseMissing('support_messages', ['sender' => 'assistant']);
    }

    public function test_escalated_customers_get_exact_fifo_queue_positions_and_capacity_is_respected(): void
    {
        Http::preventStrayRequests();

        $workspace = Workspace::factory()->create([
            'escalation_keywords' => ['cancel my subscription'],
            'max_active_chats' => 1,
        ]);
        $agent = User::factory()->agent($workspace)->create([
            'is_available' => true,
            'last_seen_at' => now(),
        ]);

        $first = $this->startEscalatedConversation($workspace, 'First customer');
        $second = $this->startEscalatedConversation($workspace, 'Second customer');
        $third = $this->startEscalatedConversation($workspace, 'Third customer');

        $this->assertDatabaseHas('support_conversations', [
            'id' => $first['conversation_id'],
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
        $this->assertDatabaseHas('support_conversations', [
            'id' => $second['conversation_id'],
            'status' => 'waiting',
        ]);
        $this->assertDatabaseHas('support_conversations', [
            'id' => $third['conversation_id'],
            'status' => 'waiting',
        ]);

        $this->postJson(route('support.conversations.messages', $third['conversation_id']), [
            'visitor_token' => $third['visitor_token'],
            'after_id' => 0,
        ])->assertOk()->assertJsonPath('queue_position', 2);

        $this->postJson(route('support.conversations.messages', $second['conversation_id']), [
            'visitor_token' => $second['visitor_token'],
            'after_id' => 0,
        ])->assertOk()->assertJsonPath('queue_position', 1);
    }

    public function test_customer_token_is_required_to_read_conversation_messages(): void
    {
        $conversation = SupportConversation::factory()->create();

        $this->postJson(route('support.conversations.messages', $conversation), [
            'visitor_token' => 'wrong-token',
        ])->assertNotFound();
    }

    public function test_customer_leaving_chat_ends_it_and_releases_the_agent_for_the_next_customer(): void
    {
        $workspace = Workspace::factory()->create(['max_active_chats' => 1]);
        $agent = User::factory()->agent($workspace)->create([
            'is_available' => true,
            'last_seen_at' => now(),
        ]);
        $visitorToken = 'visitor-token-for-ending-chat';
        $conversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'agent_id' => $agent->id,
            'visitor_token_hash' => hash('sha256', $visitorToken),
            'status' => 'assigned',
        ]);
        $waitingConversation = SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'waiting',
        ]);

        $this->postJson(route('support.conversations.end', $conversation), [
            'visitor_token' => $visitorToken,
        ])->assertOk()->assertJsonPath('closed', true);

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'status' => 'closed',
        ]);
        $this->assertDatabaseHas('support_conversations', [
            'id' => $waitingConversation->id,
            'agent_id' => $agent->id,
            'status' => 'assigned',
        ]);
    }

    public function test_customer_cannot_end_another_customers_conversation(): void
    {
        $conversation = SupportConversation::factory()->create(['status' => 'assigned']);

        $this->postJson(route('support.conversations.end', $conversation), [
            'visitor_token' => 'wrong-visitor-token',
        ])->assertNotFound();

        $this->assertDatabaseHas('support_conversations', [
            'id' => $conversation->id,
            'status' => 'assigned',
        ]);
    }

    public function test_waiting_chats_are_balanced_across_online_agents_before_entering_the_queue(): void
    {
        Http::preventStrayRequests();

        $workspace = Workspace::factory()->create([
            'escalation_keywords' => ['speak to a person'],
            'max_active_chats' => 2,
        ]);
        $agents = User::factory()
            ->count(3)
            ->agent($workspace)
            ->state([
                'is_available' => true,
                'last_seen_at' => now(),
            ])
            ->create();

        for ($chatNumber = 1; $chatNumber <= 7; $chatNumber++) {
            $response = $this->postJson(route('support.conversations.store', $workspace->public_token), [
                'customer_name' => "Customer {$chatNumber}",
                'message' => 'I want to speak to a person.',
            ]);

            $response->assertOk();

            if ($chatNumber <= 6) {
                $this->assertDatabaseHas('support_conversations', [
                    'id' => $response->json('conversation_id'),
                    'status' => 'assigned',
                ]);
            } else {
                $response->assertJsonPath('status', 'waiting')
                    ->assertJsonPath('queue_position', 1);
            }
        }

        foreach ($agents as $agent) {
            $this->assertSame(
                2,
                SupportConversation::query()
                    ->where('agent_id', $agent->id)
                    ->where('status', 'assigned')
                    ->count(),
            );
        }
    }

    /**
     * @return array{conversation_id: int, visitor_token: string}
     */
    private function startEscalatedConversation(Workspace $workspace, string $customerName): array
    {
        $response = $this->postJson(route('support.conversations.store', $workspace->public_token), [
            'customer_name' => $customerName,
            'message' => 'Please cancel my subscription.',
        ]);

        $response->assertOk();

        return [
            'conversation_id' => $response->json('conversation_id'),
            'visitor_token' => $response->json('visitor_token'),
        ];
    }
}
