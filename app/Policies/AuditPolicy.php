<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\User;

class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Audit $audit): bool
    {
        return $user->id === $audit->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Audit $audit): bool
    {
        return $user->id === $audit->user_id;
    }
}
