<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\Priority;
use App\Enums\TaskKind;

class Soldier
{
    public $id;

    public $pointsMaxData;

    public $shiftsMaxData;

    public $nightsMaxData;

    public $weekendsMaxData;

    public $alertsMaxData;

    public $inParallelMaxData;

    public $qualifications;

    public $constraints;

    public $shifts;

    public $concurrentsShifts;

    public function __construct($id, MaxData $maxPoints, MaxData $maxShifts, MaxData $maxNights, MaxData $maxWeekends, MaxData $alertsMaxData, MaxData $inParallelMaxData, $qualifications, $constraints, $shifts = [], $concurrentsShifts = [])
    {
        $this->id = $id;
        $this->pointsMaxData = $maxPoints;
        $this->shiftsMaxData = $maxShifts;
        $this->nightsMaxData = $maxNights;
        $this->weekendsMaxData = $maxWeekends;
        $this->alertsMaxData = $alertsMaxData;
        $this->inParallelMaxData = $inParallelMaxData;
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
            && $this->isAvailableByShifts($shift)
            && $this->isAvailableBySpaces($spaces);
    }

    public function isAvailableByMaxes(Shift $shift): bool
    {
        if (
            ($shift->kind === TaskKind::WEEKEND->value && $this->weekendsMaxData->remaining() < $shift->points)
            || ($shift->kind === TaskKind::NIGHT->value && $this->nightsMaxData->remaining() < 1)
            || ($shift->kind === TaskKind::ALERT->value && $this->alertsMaxData->remaining() < 1)
            || ($shift->kind === TaskKind::INPARALLEL->value && $this->inParallelMaxData->remaining() < 1)
            || $this->pointsMaxData->remaining() < $shift->points
            || $this->shiftsMaxData->remaining() < 1
        ) {
            return false;
        }

        return true;
    }

    public function isAvailableByShifts(Shift $shift): bool
    {
        return ! $this->shifts->contains(function (Shift $soldierShift) use ($shift): bool {
            return $soldierShift->range->isConflict($shift->range) && ! collect($shift->inParalelTasks)->contains($shift->taskType);
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
            return $concurrentsShift->range->isConflict($shift->range) && ! collect($concurrentsShift->inParalelTasks)->contains($shift->taskType);
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
        match ($shift->kind) {
            TaskKind::WEEKEND->value => $this->weekendsMaxData->used += $shift->points,
            TaskKind::NIGHT->value => $this->updateNightAndShifts(),
            TaskKind::ALERT->value => $this->alertsMaxData->used += 1,
            TaskKind::INPARALLEL->value => $this->inParallelMaxData->used += 1,
            TaskKind::REGULAR->value => $this->shiftsMaxData->used += 1,
        };
    }

    protected function addSpaces($spaces)
    {
        collect($spaces)->map(fn ($space) => $this->shifts->push(new Shift(0, '', $space->start, $space->end, 0, TaskKind::REGULAR->value, [])));
    }

    protected function updateNightAndShifts()
    {
        $this->nightsMaxData->used += 1;
        $this->shiftsMaxData->used += 1;
    }
}
