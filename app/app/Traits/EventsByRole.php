<?php

namespace App\Traits;

use App\Models\Department;
use App\Models\Team;

trait EventsByRole
{
    public static function getEventsByRole($query)
    {
        $currentUserId = auth()->user()->userable_id;
        $role = current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']));
        $query = match ($role) {
            'manager', 'shifts-assignment' => $query->where(function ($query) use ($currentUserId) {
                $query->where('soldier_id', '!=', $currentUserId)
                    ->orWhereNull('soldier_id');
            }),
            'department-commander' => $query->where(function ($query) use ($currentUserId) {
                $query->where('soldier_id', '!=', $currentUserId)
                    ->where(function ($query) use ($currentUserId) {
                        $query->whereNull('soldier_id')
                            ->orWhereIn('soldier_id', Department::whereHas('commander', function ($query) use ($currentUserId) {
                                $query->where('id', $currentUserId);
                            })->first()?->teams->flatMap(fn (Team $team) => $team->members->pluck('id'))->toArray() ?? collect([]))
                            ->orWhereIn('soldier_id', Department::whereHas('commander', function ($query) use ($currentUserId) {
                                $query->where('id', $currentUserId);
                            })->first()?->teams->pluck('commander_id') ?? collect([]));
                    })->orWhereNull('soldier_id');
            }),
            'team-commander' => $query->where(function ($query) use ($currentUserId) {
                $query->where('soldier_id', '!=', $currentUserId)
                    ->where(function ($query) use ($currentUserId) {
                        $query->whereNull('soldier_id')
                            ->orWhereIn('soldier_id', Team::whereHas('commander', function ($query) use ($currentUserId) {
                                $query->where('id', $currentUserId);
                            })->first()?->members->pluck('id') ?? collect([]));
                    })
                    ->orWhereNull('soldier_id');
            }),
        };

        return $query;
    }
}
