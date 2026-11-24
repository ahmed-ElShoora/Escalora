<?php

namespace App\Actions;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivateAgentAction
{
    public function handle(User $agent, string $password): User
    {
        if (! $agent->isAgent() || ! $agent->hasValidInvitation()) {
            throw ValidationException::withMessages([
                'token' => 'This invitation link is invalid or has expired.',
            ]);
        }

        return DB::transaction(function () use ($agent, $password): User {
            $agent->forceFill([
                'password' => $password,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'invitation_token' => null,
                'invitation_expires_at' => null,
            ])->save();

            return $agent->refresh();
        });
    }
}
