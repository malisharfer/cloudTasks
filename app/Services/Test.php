<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;

class Test
{
    public function test()
    {
        $shifts = Shift::whereNotNull('soldier_id')->get()->groupBy('soldier_id');
        $soldiersDetails = collect();
        $shifts->each(callback: function ($shifts, $soldier_id) use ($soldiersDetails) {
            $user = User::where('userable_id', $soldier_id)->first();
            $soldiersDetails->push([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'nights' => $this->howMuchNights($shifts),
                'weekends' => $this->howMuchWeekends($shifts),
                'shifts' => $shifts->count(),
                'points' => $this->howMuchPoints($shifts),
            ]);
        });
    }

    protected function howMuchNights($shifts)
    {
        return $shifts->filter(fn ($shift) => $shift->task->is_night)->count();
    }

    protected function howMuchWeekends($shifts)
    {
        return $shifts->filter(fn ($shift) => $shift->is_weekend != null ? $shift->is_weekend : $shift->task->is_weekend)->count();
    }

    protected function howMuchPoints($shifts)
    {
        return collect($shifts)->sum(fn ($shift) => $shift->parallel_weight != null ? $shift->parallel_weight : $shift->task->parallel_weight);
    }
}
