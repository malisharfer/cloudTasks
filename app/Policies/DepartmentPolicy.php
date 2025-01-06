<?php

namespace App\Policies;

use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['manager', 'shifts-assignment']);
    }
}
