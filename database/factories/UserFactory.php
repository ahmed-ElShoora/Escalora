<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'company_id' => Company::factory(),
            'workspace_id' => null,
            'remember_token' => Str::random(10),
            'invitation_token' => null,
            'invitation_expires_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'workspace_id' => null,
        ]);
    }

    public function agent(?Workspace $workspace = null): static
    {
        return $this->state(function (array $attributes) use ($workspace) {
            $workspace ??= Workspace::factory()->create([
                'company_id' => $attributes['company_id'] ?? Company::factory(),
            ]);

            return [
                'role' => UserRole::Agent,
                'status' => UserStatus::Active,
                'company_id' => $workspace->company_id,
                'workspace_id' => $workspace->id,
                'email_verified_at' => now(),
            ];
        });
    }

    public function pendingInvitation(?Workspace $workspace = null): static
    {
        return $this->agent($workspace)->state(fn (array $attributes) => [
            'password' => null,
            'status' => UserStatus::Pending,
            'email_verified_at' => null,
            'invitation_token' => hash('sha256', 'test-invitation-token'),
            'invitation_expires_at' => now()->addDays(3),
        ]);
    }
}
