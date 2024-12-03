<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Models\Constraint as ConstraintModel;
use App\Models\Shift;
use App\Models\User;

class Test
{
    public function test($month = null)
    {
        $month ??= now()->addMonth();
        $shifts = Shift::whereNotNull('soldier_id')
            ->get()
            ->filter(function (Shift $shift) use ($month): bool {
                $range = new Range(
                    $shift->start_date,
                    $shift->end_date,
                );

                return $range->isSameMonth(new Range($month->startOfMonth(), $month->endOfMonth()));
            })
            ->groupBy('soldier_id');

        $soldiersDetails = collect();

        $shifts->each(function ($shifts, $soldier_id) use ($soldiersDetails) {
            $user = User::where('userable_id', $soldier_id)->first();
            $constraints = ConstraintModel::where('soldier_id', $soldier_id)->get();
            $soldiersDetails->push([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'nights' => $this->howMuchNights($shifts),
                'weekends' => $this->howMuchWeekends($shifts),
                'shifts' => $shifts->count(),
                'points' => $this->howMuchPoints($shifts),
                'constraints' => $constraints,
                'lowConstraintsRejected' => $this->howMuchLowConstraintsRejected($constraints, $shifts),
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

    protected function howMuchLowConstraintsRejected($constraints, $shifts): int
    {
        $count = 0;
        $constraints->filter(
            fn (ConstraintModel $constraint) => ConstraintType::getPriority()[$constraint->constraint_type] == 2
        )
            ->map(
                function ($constraint) use ($count, $shifts) {
                    $shifts->map(function ($shift) use ($constraint, $count) {
                        $range = new Range(
                            $shift->start_date,
                            $shift->end_date,
                        );
                        $range->isConflict(new Range($constraint->start_date, $constraint->end_date)) ?
                            $count++ : $count;
                    });
                }
            );

        return $count;
    }
}