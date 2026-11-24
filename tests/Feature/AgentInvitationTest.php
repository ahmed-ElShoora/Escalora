<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Mail\AgentInvitationMail;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgentInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_agent_and_agent_can_activate(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin)
            ->post(route('admin.workspaces.agents.store', $workspace), [
                'name' => 'Sam Agent',
                'email' => 'sam@example.com',
            ])
            ->assertRedirect();

        $agent = User::query()->where('email', 'sam@example.com')->first();

        $this->assertNotNull($agent);
        $this->assertTrue($agent->isPendingInvitation());
        $this->assertSame($workspace->id, $agent->workspace_id);

        Mail::assertSent(AgentInvitationMail::class, function (AgentInvitationMail $mail) use ($agent): bool {
            return $mail->agent->is($agent);
        });

        $plainToken = 'plain-token-for-test';
        $agent->forceFill([
            'invitation_token' => hash('sha256', $plainToken),
            'invitation_expires_at' => now()->addDay(),
        ])->save();

        $this->post(route('logout'));

        $this->post(route('agent.invitation.store', $plainToken), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('agent.dashboard'));

        $agent->refresh();

        $this->assertSame(UserStatus::Active, $agent->status);
        $this->assertNotNull($agent->password);
        $this->assertNull($agent->invitation_token);
        $this->assertAuthenticatedAs($agent);
    }

    public function test_duplicate_agent_email_is_rejected_globally(): void
    {
        $firstAdmin = User::factory()->admin()->create();
        $firstWorkspace = Workspace::factory()->create(['company_id' => $firstAdmin->company_id]);
        User::factory()->agent($firstWorkspace)->create([
            'email' => 'taken@example.com',
        ]);

        $secondAdmin = User::factory()->admin()->create();
        $secondWorkspace = Workspace::factory()->create(['company_id' => $secondAdmin->company_id]);

        $this->actingAs($secondAdmin)
            ->post(route('admin.workspaces.agents.store', $secondWorkspace), [
                'name' => 'Another Person',
                'email' => 'taken@example.com',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'taken@example.com')->count());
    }

    public function test_admin_can_remove_agent_and_email_becomes_reusable(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create(['company_id' => $admin->company_id]);
        $agent = User::factory()->agent($workspace)->create([
            'email' => 'reusable@example.com',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.workspaces.agents.destroy', [$workspace, $agent]))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'reusable@example.com']);

        $otherAdmin = User::factory()->admin()->create();
        $otherWorkspace = Workspace::factory()->create(['company_id' => $otherAdmin->company_id]);

        $this->actingAs($otherAdmin)
            ->post(route('admin.workspaces.agents.store', $otherWorkspace), [
                'name' => 'Reusable Agent',
                'email' => 'reusable@example.com',
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'reusable@example.com',
            'company_id' => $otherAdmin->company_id,
        ]);
    }

    public function test_active_agent_can_login_on_agent_login_page(): void
    {
        $agent = User::factory()->agent()->create([
            'email' => 'frontline@example.com',
            'password' => 'password123',
        ]);

        $this->post(route('agent.login.store'), [
            'email' => 'frontline@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('agent.dashboard'));

        $this->assertAuthenticatedAs($agent);
        $this->assertTrue($agent->fresh()->isOnline());
    }
}
