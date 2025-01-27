<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Enums\Priority;
use App\Models\Constraint;
use App\Models\Shift;
use App\Services\Constraint as ConstraintService;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;

class Helpers
{
    public static function buildShift(Shift $shift): ShiftService
    {
        return new ShiftService(
            $shift->id,
            $shift->task->type,
            $shift->start_date,
            $shift->end_date,
            $shift->parallel_weight === null ? $shift->task->parallel_weight : $shift->parallel_weight,
            $shift->task->is_night,
            $shift->is_weekend !== null ? $shift->is_weekend : $shift->task->is_weekend,
        );
    }

    public static function buildSoldier($soldier, $constraints, $shifts, array $capacityHold, $concurrentsShifts = []): SoldierService
    {
        return new SoldierService(
            $soldier->id,
            new MaxData($soldier->capacity, $capacityHold['points'] ?? 0),
            new MaxData($soldier->max_shifts, $capacityHold['count'] ?? 0),
            new MaxData($soldier->max_nights, $capacityHold['sumNights'] ?? 0),
            new MaxData($soldier->max_weekends, $capacityHold['sumWeekends'] ?? 0),
            $soldier->qualifications,
            $constraints,
            $shifts,
            $concurrentsShifts
        );
    }

    public static function buildConstraints($constraints, $newRange)
    {
        return $constraints
            ->filter(function (Constraint $constraint) use ($newRange) {
                $range = new Range($constraint->start_date, $constraint->end_date);

                return $range->isSameMonth($newRange);
            })
            ->map(
                fn (Constraint $constraint): ConstraintService => new ConstraintService(
                    $constraint->start_date,
                    $constraint->end_date,
                    ConstraintType::getPriority()[$constraint->constraint_type] == 1 ? Priority::HIGH : Priority::LOW
                )
            );
    }

    public static function capacityHold($shifts): array
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

    public static function addShiftsSpaces($shifts)
    {
        $allSpaces = collect([]);
        collect($shifts)->map(function (ShiftService $shift) use ($shifts, &$allSpaces) {
            $spaces = $shift->isWeekend || $shift->isNight ? $shift->getShiftSpaces($shifts) : null;
            if (! empty($spaces)) {
                collect($spaces)->map(fn (Range $space) => $allSpaces->push(new ShiftService(0, '', $space->start, $space->end, 0, false, false)));
            }
        });

        return $allSpaces;
    }

    public static function getSoldiersShifts($soldierId, $newRange, $inParallel)
    {
        return Shift::where('soldier_id', $soldierId)
            ->get()
            ->filter(
                function (Shift $shift) use ($newRange, $inParallel): bool {
                    $range = new Range($shift->start_date, $shift->end_date);

                    return $range->isSameMonth($newRange) && $shift->task->in_parallel === $inParallel;
                }
            )
            ->map(fn (Shift $shift): ShiftService => self::buildShift($shift));
    }

    public static function getConstraintBy(int $soldierId, $newRange)
    {
        $constraint = Constraint::where('soldier_id', $soldierId)
            ->get();

        return self::buildConstraints($constraint, $newRange);
    }

    public static function updateShiftTable($assignments)
    {
        collect($assignments)->map(fn (Assignment $assignment) => Shift::where('id', $assignment->shiftId)->update(['soldier_id' => $assignment->soldierId]));
    }
}
