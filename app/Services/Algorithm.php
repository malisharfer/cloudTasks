<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Enums\Priority;
use App\Models\Shift;
use App\Models\Soldier;

class Algorithm
{
    protected function getShiftWithTasks()
    {
        $shifts = Shift::whereNull('soldier_id')
            ->get()
            ->map(
                fn (Shift $shift) => new \App\Services\Shift(
                    $shift->id,
                    $shift->task->type,
                    $shift->start_date,
                    $shift->end_date,
                    $shift->parallel_weight == 0 ? $shift->task->parallel_weight : $shift->parallel_weight,
                    $shift->task->is_night,
                    $shift->is_weekend != null ? $shift->is_weekend : $shift->task->is_weekend
                )
            );

        return $shifts;
    }

    protected function getSoldiersDetails()
    {
        return Soldier::with('constraints')
            ->where('is_reservist', false)
            ->get()
            ->map(function (Soldier $soldier) {
                $constraints = $soldier->constraints->map(
                    fn ($constraint) => new \App\Services\Constraint(
                        $constraint->start_date,
                        $constraint->end_date,
                        ConstraintType::getPriority()[$constraint->constraint_type] == 1 ? Priority::HIGH : Priority::LOW
                    )
                );
                $shifts = $this->getSoldiersShifts($soldier);

                $shifts->push(...$this->addShiftsSpaces($shifts));

                $soldierSums = $this->soldierSum($shifts);

                return new \App\Services\Soldier(
                    $soldier->id,
                    new MaxData($soldier->capacity, $soldierSums['points']),
                    new MaxData($soldier->max_shifts, $soldierSums['count']),
                    new MaxData($soldier->max_nights, $soldierSums['sumNights']),
                    new MaxData($soldier->max_weekends, $soldierSums['sumWeekends']),
                    $soldier->qualifications,
                    $constraints,
                    $shifts
                );
            })
            ->shuffle()
            ->toArray();
    }

    protected function getSoldiersShifts(Soldier $soldier)
    {
        return Shift::where('soldier_id', $soldier->id)
            ->get()
            ->filter(
                function (Shift $shift): bool {
                    $range = new Range($shift->start_date, $shift->end_date);

                    return $range->isSameMonth(new Range(now()->addMonth(), now()->addMonth()));
                }
            )->map(fn (Shift $shift) => new \App\Services\Shift(
                $shift->id,
                $shift->task->type,
                $shift->start_date,
                $shift->end_date,
                $shift->parallel_weight == 0 ? $shift->task->parallel_weight : $shift->parallel_weight,
                $shift->task->is_night,
                $shift->is_weekend != null ? $shift->is_weekend : $shift->task->is_weekend,
            ));
    }

    protected function addShiftsSpaces($shifts)
    {
        $allSpaces = collect([]);
        collect($shifts)->map(function (\App\Services\Shift $shift) use ($shifts, &$allSpaces) {
            if ($shift->isWeekend) {
                $spaces = $shift->getShiftSpaces($shifts);
            }
            if ($shift->isNight) {
                $spaces = $shift->getShiftSpaces($shifts);
            }
            if (! empty($spaces)) {
                collect($spaces)->map(fn (Range $space) => $allSpaces->push(new \App\Services\Shift(0, 'space', $space->start, $space->end, 0, false, false)));
            }
        });

        return $allSpaces;
    }

    protected function soldierSum($shifts)
    {
        $points = 0;
        $nights = 0;
        $weekends = 0;
        $count = 0;
        collect($shifts)
            ->filter(function ($shift) {
                return $shift->id != 0;
            })
            ->map(function ($shift) use (&$count, &$points, &$nights, &$weekends) {
                $count++;
                $points += $shift->points;
                $shift->isWeekend ? $weekends += $shift->points : $weekends;
                $shift->isNight ? $nights += $shift->points : $nights;
            });

        return [
            'count' => $count,
            'points' => $points,
            'sumWeekends' => $weekends,
            'sumNights' => $nights,
        ];
    }

    public function run()
    {
        $shifts = $this->getShiftWithTasks();
        $soldiers = $this->getSoldiersDetails();
        $scheduleAlgorithm = new Schedule($shifts, $soldiers);
        $scheduleAlgorithm->schedule();
    }
}