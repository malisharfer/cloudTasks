<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\Priority;
use App\Enums\TaskKind;

class Soldier
{
    public $id;

    public $course;

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

    public function __construct($id, $course, MaxData $maxPoints, MaxData $maxShifts, MaxData $maxNights, MaxData $maxWeekends, MaxData $alertsMaxData, MaxData $inParallelMaxData, $qualifications, $constraints, $shifts = [], $concurrentsShifts = [])
    {
        $this->id = $id;
        $this->course = $course;
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

    public function hasMaxes(): bool
    {
        return ! (
            $this->shiftsMaxData->max === 0 &&
            $this->nightsMaxData->max === 0 &&
            $this->weekendsMaxData->max === 0 &&
            $this->alertsMaxData->max === 0
        );
    }

    public function isQualified(string $taskType): bool
    {
        return $this->qualifications->contains($taskType);
    }

    public function isAbleTake(Shift $shift, $ignoreLowConstraint): bool
    {
        $spaces = $shift->getShiftSpaces($this->shifts);
        $availability = $this->isAvailableByConstraints($shift->range);
        $isAvailableByConstraint = $ignoreLowConstraint ? $availability != Availability::NO : $availability == Availability::YES;

        return $this->isQualified($shift->taskType)
            && $this->isAvailableByMaxes($shift)
            && $this->isAvailableByShifts($shift)
            && $this->isAvailableBySpaces($spaces)
            && $isAvailableByConstraint;
    }

    public function isAvailableByMaxes(Shift $shift): bool
    {
        if ($this->pointsMaxData->remaining() < $shift->points) {
            return false;
        }

        return match ($shift->kind) {
            TaskKind::WEEKEND->value => $this->weekendsMaxData->remaining() >= $shift->points,
            TaskKind::NIGHT->value => $this->nightsMaxData->remaining() > 0 && $this->shiftsMaxData->remaining() > 0,
            TaskKind::INPARALLEL->value => $this->inParallelMaxData->remaining() > 0,
            TaskKind::ALERT->value => $this->alertsMaxData->remaining() > 0,
            TaskKind::REGULAR->value => $this->shiftsMaxData->remaining() > 0,
        };
    }

    public function isAvailableByShifts(Shift $shift): bool
    {
        return ! $this->shifts->contains(fn (Shift $soldierShift) => $soldierShift->range->isConflict($shift->range)
            && (! collect($shift->inParalelTasks)->contains($soldierShift->taskType))
        );
    }

    public function isAvailableBySpaces($spaces): bool
    {
        if ($spaces) {
            foreach ($spaces as $space) {
                if (
                    $this->shifts->contains(
                        fn(Shift $shift) => $shift->id != 0 &&
                        $shift->range->isConflict($space)
                    )
                    || $this->concurrentsShifts->contains(fn(Shift $concurrentsShift) => $concurrentsShift->range->isConflict($space))
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isAvailableByConcurrentsShifts(Shift $shift)
    {
        return ! $this->concurrentsShifts->contains(fn (Shift $concurrentsShift) => $concurrentsShift->range->isConflict($shift->range) &&
            ! (collect($concurrentsShift->inParalelTasks)->contains($shift->taskType)
                || collect($shift->inParalelTasks)->contains($concurrentsShift->taskType)));
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
            $conflicts->contains(fn ($conflict) => $conflict->priority == Priority::HIGH)
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
            TaskKind::NIGHT->value => [
                $this->nightsMaxData->used += 1,
                $this->shiftsMaxData->used += 1,
            ],
            TaskKind::ALERT->value => $this->alertsMaxData->used += 1,
            TaskKind::INPARALLEL->value => $this->inParallelMaxData->used += 1,
            TaskKind::REGULAR->value => $this->shiftsMaxData->used += 1,
        };
    }

    protected function addSpaces($spaces)
    {
        collect($spaces)->map(fn ($space) => $this->shifts->push(new Shift(0, 0, '', $space->start, $space->end, 0, TaskKind::REGULAR->value, [])));
    }

    public function unassign(Shift $shift, $spaces)
    {
        $this->shifts = $this->shifts->filter(fn (Shift $existShift) => $shift->id !== $existShift->id);
        $this->removeSpaces($spaces);
        $this->pointsMaxData->used -= $shift->points;
        match ($shift->kind) {
            TaskKind::WEEKEND->value => $this->weekendsMaxData->used -= $shift->points,
            TaskKind::NIGHT->value => [
                $this->nightsMaxData->used -= 1,
                $this->shiftsMaxData->used -= 1,
            ],
            TaskKind::ALERT->value => $this->alertsMaxData->used -= 1,
            TaskKind::INPARALLEL->value => $this->inParallelMaxData->used -= 1,
            TaskKind::REGULAR->value => $this->shiftsMaxData->used -= 1,
        };
    }

    protected function removeSpaces($spaces)
    {
        collect($spaces)->map(function ($space) {
            $this->shifts = $this->shifts->filter(fn (Shift $existSpace) => $space->start !== $existSpace->range->start && $space->end !== $existSpace->range->end);
        });
    }
}
