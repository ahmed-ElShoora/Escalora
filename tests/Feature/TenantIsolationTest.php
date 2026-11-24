<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_view_another_company_workspace(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $workspaceB = Workspace::factory()->create(['company_id' => $adminB->company_id]);

        $this->actingAs($adminA)
            ->get(route('admin.workspaces.show', $workspaceB))
            ->assertForbidden();
    }

    public function test_admin_cannot_invite_into_another_company_workspace(): void
    {
        $adminA = User::factory()->admin()->create();
        $adminB = User::factory()->admin()->create();
        $workspaceB = Workspace::factory()->create(['company_id' => $adminB->company_id]);

        $this->actingAs($adminA)
            ->post(route('admin.workspaces.agents.store', $workspaceB), [
                'name' => 'Intruder',
                'email' => 'intruder@example.com',
            ])
            ->assertForbidden();
    }

    public function test_agent_cannot_access_admin_dashboard(): void
    {
        $agent = User::factory()->agent()->create();

        $this->actingAs($agent)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_agent_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('agent.dashboard'))
            ->assertForbidden();
    }
}
