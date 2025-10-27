<?php

namespace App\Policies;

use App\Models\Soldier;
use App\Models\User;

class ConstraintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['manager', 'shifts-assignment', 'department-commander', 'team-commander'])
            || ($user->hasRole('soldier') && Soldier::find($user->userable_id)->team !== null);
    }
}
