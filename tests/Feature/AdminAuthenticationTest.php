<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_and_must_verify_email_before_dashboard(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'company' => 'Acme Support',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertSame('Acme Support', $admin->company?->name);
        $this->assertNull($admin->email_verified_at);

        Notification::assertSentTo($admin, VerifyEmail::class);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_admin_can_verify_email_and_reach_dashboard(): void
    {
        $admin = User::factory()->admin()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $this->actingAs($admin)
            ->get($verificationUrl)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue($admin->fresh()->hasVerifiedEmail());
    }

    public function test_admin_can_login_and_logout(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'boss@example.com',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => 'boss@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_agent_cannot_use_admin_login(): void
    {
        $agent = User::factory()->agent()->create([
            'email' => 'agent@example.com',
            'password' => 'password123',
        ]);

        $this->post(route('login.store'), [
            'email' => 'agent@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(UserRole::Agent, $agent->role);
    }
}
