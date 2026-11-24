<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_update_workspace(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.workspaces.store'), [
                'name' => 'Billing Desk',
                'description' => 'Handles billing escalations',
            ])
            ->assertRedirect();

        $workspace = Workspace::query()->where('name', 'Billing Desk')->first();

        $this->assertNotNull($workspace);
        $this->assertSame($admin->company_id, $workspace->company_id);

        $this->actingAs($admin)
            ->put(route('admin.workspaces.update', $workspace), [
                'name' => 'Billing HQ',
                'description' => 'Updated description',
            ])
            ->assertRedirect(route('admin.workspaces.show', $workspace));

        $this->assertSame('Billing HQ', $workspace->fresh()->name);
    }

    public function test_admin_can_delete_workspace(): void
    {
        $admin = User::factory()->admin()->create();
        $workspace = Workspace::factory()->create(['company_id' => $admin->company_id]);

        $this->actingAs($admin)
            ->delete(route('admin.workspaces.destroy', $workspace))
            ->assertRedirect(route('admin.workspaces.index'));

        $this->assertDatabaseMissing('workspaces', ['id' => $workspace->id]);
    }
}
