<?php

namespace App\Policies;

use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['manager', 'shifts-assignment', 'department-commander', 'team-commander']);
    }
}
