<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\AgentInvitationMail;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteAgentAction
{
    /**
     * @param  array{name: string, email: string}  $data
     */
    public function handle(Workspace $workspace, array $data, User $admin): User
    {
        $email = Str::lower($data['email']);

        if (User::query()->where('email', $email)->exists()) {
            $this->throwEmailTakenException();
        }

        $plainToken = Str::random(64);

        try {
            $agent = DB::transaction(function () use ($workspace, $data, $email, $plainToken): User {
                return User::query()->create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => null,
                    'role' => UserRole::Agent,
                    'status' => UserStatus::Pending,
                    'company_id' => $workspace->company_id,
                    'workspace_id' => $workspace->id,
                    'invitation_token' => hash('sha256', $plainToken),
                    'invitation_expires_at' => now()->addDays(3),
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueEmailViolation($exception)) {
                $this->throwEmailTakenException();
            }

            throw $exception;
        }

        $agent->load(['workspace', 'company']);

        Mail::to($agent->email)->send(new AgentInvitationMail($agent, $plainToken, $admin));

        return $agent;
    }

    private function throwEmailTakenException(): never
    {
        throw ValidationException::withMessages([
            'email' => 'This email is already associated with another company. The existing company admin must remove the agent before you can invite them.',
        ]);
    }

    private function isUniqueEmailViolation(QueryException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, ['unique', 'duplicate']) && Str::contains($message, 'email');
    }
}
