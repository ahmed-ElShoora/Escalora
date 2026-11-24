<?php

namespace Tests\Feature;

use App\Models\SupportConversation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceSupportSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_configures_escalation_phrases_and_maximum_agent_capacity(): void
    {
        $admin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin)
            ->put(route('admin.workspaces.support-settings.update', $workspace), [
                'escalation_keywords' => "cancel my subscription\nI need a human\n cancel my subscription ",
                'max_active_chats' => 3,
            ])
            ->assertRedirect();

        $this->assertSame(
            ['cancel my subscription', 'I need a human'],
            $workspace->fresh()->escalation_keywords,
        );
        $this->assertSame(3, $workspace->fresh()->max_active_chats);
    }

    public function test_workspace_dashboard_filters_chat_counts_and_ratings_by_period(): void
    {
        $admin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create(['company_id' => $admin->company_id]);

        SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'closed',
            'rating' => 4,
            'rated_at' => now()->subDays(10),
            'created_at' => now()->subDays(10),
        ]);
        SupportConversation::factory()->create([
            'workspace_id' => $workspace->id,
            'status' => 'closed',
            'rating' => 2,
            'rated_at' => now()->subDays(40),
            'created_at' => now()->subDays(40),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.workspaces.show', ['workspace' => $workspace, 'period' => 'month']))
            ->assertOk()
            ->assertSee('Last 30 days')
            ->assertSee('4.0 / 5')
            ->assertSee(route('support.show', $workspace->public_token))
            ->assertSee('Copy HTML');
    }

    public function test_admin_cannot_change_support_settings_for_another_company(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create(['company_id' => $otherAdmin->company_id]);

        $this->actingAs($admin)
            ->put(route('admin.workspaces.support-settings.update', $workspace), [
                'escalation_keywords' => 'cancel',
                'max_active_chats' => 5,
            ])
            ->assertForbidden();

        $this->assertSame([], $workspace->fresh()->escalation_keywords ?? []);
    }
}
