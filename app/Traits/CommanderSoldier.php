<?php

namespace App\Traits;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;

trait CommanderSoldier
{
    public static function getCommanderSoldier()
    {
        $currentUserId = auth()->user()->userable_id;
        $role = current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
        $query = User::query();
        $query = match ($role) {
            'manager', 'shifts-assignment' => $query->where(function ($query) use ($currentUserId) {
                $query->where('userable_id', '!=', $currentUserId);
            }),
            'department-commander' => $query->where(function ($query) use ($currentUserId) {
                $query->where('userable_id', '!=', $currentUserId)
                    ->where(function ($query) use ($currentUserId) {
                        $query
                            ->whereIn('userable_id', Department::whereHas('commander', function ($query) use ($currentUserId) {
                                $query->where('id', $currentUserId);
                            })->first()?->teams->flatMap(fn (Team $team) => $team->members->pluck('id'))->toArray() ?? collect([]))
                            ->orWhereIn('userable_id', Department::whereHas('commander', function ($query) use ($currentUserId) {
                                $query->where('id', $currentUserId);
                            })->first()?->teams->pluck('commander_id') ?? collect([]));
                    });
            }),
            'team-commander' => $query->where(function ($query) use ($currentUserId) {
                $query->where('userable_id', '!=', $currentUserId)
                    ->where(function ($query) use ($currentUserId) {
                        $query->whereIn('userable_id', Team::whereHas('commander', function ($query) use ($currentUserId) {
                            $query->where('id', $currentUserId);
                        })->first()?->members->pluck('id') ?? collect([]));
                    });
            }),
        };

        return $query->get()->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName]);
    }
}
