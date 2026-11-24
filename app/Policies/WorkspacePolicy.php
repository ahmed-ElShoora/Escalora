<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->company_id !== null;
    }

    public function view(User $user, Workspace $workspace): bool
    {
        return $user->isAdmin() && $user->company_id === $workspace->company_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->company_id !== null;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->isAdmin() && $user->company_id === $workspace->company_id;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->isAdmin() && $user->company_id === $workspace->company_id;
    }

    public function manageAgents(User $user, Workspace $workspace): bool
    {
        return $this->update($user, $workspace);
    }
}
