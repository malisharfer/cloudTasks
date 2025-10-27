<?php

namespace App\Policies;

use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['manager', 'shifts-assignment', 'department-commander', 'team-commander']);
    }
}
