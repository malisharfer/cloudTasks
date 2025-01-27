<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\Priority;
use App\Models\Shift as ShiftModel;
use App\Models\Task;

class Soldier
{
    public $id;

    public $pointsMaxData;

    public $shiftsMaxData;

    public $nightsMaxData;

    public $weekendsMaxData;

    public $qualifications;

    public $constraints;

    public $shifts;

    public $concurrentsShifts;

    public function __construct($id, MaxData $maxPoints, MaxData $maxShifts, MaxData $maxNights, MaxData $maxWeekends, $qualifications, $constraints, $shifts = [], $concurrentsShifts = [])
    {
        $this->id = $id;
        $this->pointsMaxData = $maxPoints;
        $this->shiftsMaxData = $maxShifts;
        $this->nightsMaxData = $maxNights;
        $this->weekendsMaxData = $maxWeekends;
        $this->qualifications = collect($qualifications);
        $this->constraints = collect($constraints);
        $this->shifts = collect($shifts);
        $this->concurrentsShifts = collect($concurrentsShifts);
    }

    public function isQualified(string $taskType): bool
    {
        return $this->qualifications->contains($taskType);
    }

    public function isAbleTake(Shift $shift, $spaces): bool
    {
        return $this->isAvailableByMaxes($shift)
            && $this->isAvailableByShifts($shift, false)
            && $this->isAvailableBySpaces($spaces);
    }

    public function isAvailableByMaxes(Shift $shift): bool
    {
        if (($shift->isWeekend && $this->weekendsMaxData->remaining() < $shift->points) || ($shift->isNight && $this->nightsMaxData->remaining() < $shift->points)) {
            return false;
        }

        return $this->pointsMaxData->remaining() >= $shift->points
            && $this->shiftsMaxData->remaining() >= 1;
    }

    public function isAvailableByShifts(Shift $shift, bool $inParallel): bool
    {
        return $inParallel ?
            ! $this->shifts->contains(function (Shift $soldierShift) use ($shift): bool {
                $tasksInParallel = Task::find(ShiftModel::find($shift->id)->task_id)->concurrent_tasks;

                return $soldierShift->range->isConflict($shift->range) && ! collect($tasksInParallel)->contains($shift->taskType);
            }) :
            ! $this->shifts->contains(function (Shift $soldierShift) use ($shift) {
                return $soldierShift->range->isConflict($shift->range);
            });
    }

    public function isAvailableBySpaces($spaces): bool
    {
        if ($spaces) {
            foreach ($spaces as $space) {
                return ! $this->shifts->contains(function ($shift) use ($space) {
                    return $shift->range->isConflict($space);
                });
            }
        }

        return true;
    }

    public function isAvailableByConcurrentsShifts(Shift $shift)
    {
        return ! $this->concurrentsShifts->contains(function (Shift $concurrentsShift) use ($shift): bool {
            $tasksInParallel = Task::find(ShiftModel::find($concurrentsShift->id)->task_id)->concurrent_tasks;

            return $concurrentsShift->range->isConflict($shift->range) && ! collect($tasksInParallel)->contains($shift->taskType);
        });
    }

    public function isAvailableByConstraints(Range $range): Availability
    {
        $conflicts = $this->constraints->filter(function (Constraint $constraint) use ($range) {
            return $constraint->range->isConflict($range);
        });

        if ($conflicts->isEmpty()) {
            return Availability::YES;
        }

        if (
            $conflicts->contains(function ($conflict) {
                return $conflict->priority == Priority::HIGH;
            })
        ) {
            return Availability::NO;
        }

        return Availability::BETTER_NOT;
    }

    public function assign(Shift $shift, $spaces): void
    {
        $this->shifts->push($shift);
        $this->addSpaces($spaces);
        $this->pointsMaxData->used += $shift->points;
        $this->shiftsMaxData->used += 1;
        if ($shift->isWeekend) {
            $this->weekendsMaxData->used += $shift->points;
        } elseif ($shift->isNight) {
            $this->nightsMaxData->used += $shift->points;
        }
    }

    protected function addSpaces($spaces)
    {
        collect($spaces)->map(fn ($space) => $this->shifts->push(new Shift(0, '', $space->start, $space->end, 0, false, false)));
    }
}
