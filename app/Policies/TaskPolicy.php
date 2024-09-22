<?php

namespace App\Policies;

use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['manager', 'department-commander', 'team-commander']);
    }
}
