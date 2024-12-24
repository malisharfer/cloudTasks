<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Enums\Priority;
use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Constraint as ConstraintService;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;
use Carbon\Carbon;

class Algorithm
{
    protected $date;

    public function __construct($date = null)
    {
        $this->date = $date ? Carbon::parse($date) : now()->addMonth();
    }

    protected function getShiftWithTasks()
    {
        return Shift::whereNull('soldier_id')
            ->get()
            ->filter(function (Shift $shift) {
                $range = new Range($shift->start_date, $shift->end_date);

                return $range->isSameMonth(new Range(max($this->date->copy()->startOfMonth(), Carbon::tomorrow()), $this->date->copy()->endOfMonth()));
            })
            ->map(fn (Shift $shift): ShiftService => $this->buildShift($shift));
    }

    protected function getSoldiersDetails()
    {
        return Soldier::with('constraints')
            ->where('is_reservist', false)
            ->get()
            ->map(function (Soldier $soldier) {
                $constraints = $soldier->constraints
                    ->filter(function ($constraint) {
                        $range = new Range($constraint->start_date, $constraint->end_date);

                        return $range->isSameMonth(new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()));
                    })
                    ->map(
                        fn ($constraint) => new ConstraintService(
                            $constraint->start_date,
                            $constraint->end_date,
                            ConstraintType::getPriority()[$constraint->constraint_type] == 1 ? Priority::HIGH : Priority::LOW
                        )
                    );
                $shifts = $this->getSoldiersShifts($soldier);

                $shifts->push(...$this->addShiftsSpaces($shifts));

                $capacityHold = $this->capacityHold($shifts);

                return new SoldierService(
                    $soldier->id,
                    new MaxData($soldier->capacity, $capacityHold['points']),
                    new MaxData($soldier->max_shifts, $capacityHold['count']),
                    new MaxData($soldier->max_nights, $capacityHold['sumNights']),
                    new MaxData($soldier->max_weekends, $capacityHold['sumWeekends']),
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

                    return $range->isSameMonth(new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()));
                }
            )
            ->map(fn (Shift $shift): ShiftService => $this->buildShift($shift));
    }

    protected function buildShift(Shift $shift): ShiftService
    {
        return new ShiftService(
            $shift->id,
            $shift->task->type,
            $shift->start_date,
            $shift->end_date,
            $shift->parallel_weight == 0 ? $shift->task->parallel_weight : $shift->parallel_weight,
            $shift->task->is_night,
            $shift->is_weekend != null ? $shift->is_weekend : $shift->task->is_weekend,
        );
    }

    protected function addShiftsSpaces($shifts)
    {
        $allSpaces = collect([]);
        collect($shifts)->map(function (ShiftService $shift) use ($shifts, &$allSpaces) {
            $spaces = $shift->isWeekend || $shift->isNight ? $shift->getShiftSpaces($shifts) : null;
            if (! empty($spaces)) {
                collect($spaces)->map(fn (Range $space) => $allSpaces->push(new ShiftService(0, 'space', $space->start, $space->end, 0, false, false)));
            }
        });

        return $allSpaces;
    }

    protected function capacityHold($shifts): array
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
