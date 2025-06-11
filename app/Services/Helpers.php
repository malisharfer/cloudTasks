<?php

namespace App\Services;

use App\Enums\ConstraintType;
use App\Enums\Priority;
use App\Enums\TaskKind;
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
            $shift->task_id,
            $shift->task()->withTrashed()->first()->type,
            $shift->start_date,
            $shift->end_date,
            $shift->parallel_weight === null ? $shift->task()->withTrashed()->first()->parallel_weight : $shift->parallel_weight,
            self::kind($shift),
            $shift->task()->withTrashed()->first()->concurrent_tasks
        );
    }

    public static function kind(Shift $shift)
    {
        if ($shift->is_weekend === true) {
            return TaskKind::WEEKEND->value;
        }
        if ($shift->is_weekend === false && $shift->task()->withTrashed()->first()->kind === TaskKind::WEEKEND->value) {
            return TaskKind::REGULAR->value;
        }

        return $shift->task()->withTrashed()->first()->kind;
    }

    public static function buildSoldier($soldier, $constraints, $shifts, array $capacityHold, $concurrentsShifts = []): SoldierService
    {
        return new SoldierService(
            $soldier->id,
            $soldier->course,
            new MaxData($soldier->capacity, $capacityHold['points'] ?? 0),
            new MaxData($soldier->max_shifts, $capacityHold['count'] ?? 0),
            new MaxData($soldier->max_nights, $capacityHold['sumNights'] ?? 0),
            new MaxData($soldier->max_weekends, $capacityHold['sumWeekends'] ?? 0),
            new MaxData($soldier->max_alerts, $capacityHold['sumAlerts'] ?? 0),
            new MaxData($soldier->max_in_parallel, $capacityHold['sumInParallel'] ?? 0),
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
                    ConstraintType::getPriority()[$constraint->constraint_type->value] == 1 ? Priority::HIGH : Priority::LOW,
                )
            );
    }

    public static function capacityHold($shifts): array
    {
        $points = 0;
        $nights = 0;
        $weekends = 0;
        $count = 0;
        $alerts = 0;
        $inParallel = 0;
        collect($shifts)
            ->filter(fn (ShiftService $shift) => $shift->id != 0)
            ->each(function (ShiftService $shift) use (&$count, &$points, &$nights, &$weekends, &$alerts, &$inParallel) {
                $count++;
                $points += $shift->points;
                match ($shift->kind) {
                    TaskKind::WEEKEND->value => $weekends += $shift->points,
                    TaskKind::NIGHT->value => $nights++,
                    TaskKind::ALERT->value => $alerts++,
                    TaskKind::INPARALLEL->value => $inParallel++,
                    TaskKind::REGULAR->value => null
                };
            });

        return [
            'count' => $count,
            'points' => $points,
            'sumWeekends' => $weekends,
            'sumNights' => $nights,
            'sumAlerts' => $alerts,
            'sumInParallel' => $inParallel,
        ];
    }

    public static function addShiftsSpaces($shifts)
    {
        $allSpaces = collect([]);
        collect($shifts)->map(function (ShiftService $shift) use ($shifts, &$allSpaces) {
            $spaces = ($shift->kind === TaskKind::WEEKEND->value || $shift->kind === TaskKind::NIGHT->value) ? $shift->getShiftSpaces($shifts) : null;
            if (! empty($spaces)) {
                collect($spaces)->map(fn (Range $space) => $allSpaces->push(new ShiftService(0, 0, '', $space->start, $space->end, 0, TaskKind::REGULAR->value, [])));
            }
        });

        return $allSpaces;
    }

    public static function getSoldiersShifts($soldierId, $range, $inParallel)
    {
        return Shift::with('task')
            ->where('soldier_id', $soldierId)
            ->whereHas('task', function ($query) use ($inParallel) {
                $query->withTrashed()
                    ->when($inParallel, fn ($query) => $query->where('kind', TaskKind::INPARALLEL->value))
                    ->when(! $inParallel, fn ($query) => $query->where('kind', '!=', TaskKind::INPARALLEL->value));
            })
            ->where(function ($query) use ($range) {
                $query->where(function ($subQuery) use ($range) {
                    $subQuery->where('start_date', '<', $range->end)
                        ->where('end_date', '>', $range->start);
                });
            })
            ->get()
            ->map(fn (Shift $shift): ShiftService => self::buildShift($shift));
    }

    public static function getConstraintBy(int $soldierId, $newRange)
    {
        $constraints = Constraint::where('soldier_id', $soldierId)
            ->get();

        return self::buildConstraints($constraints, $newRange);
    }

    public static function updateShiftTable($assignments)
    {
        if (empty($assignments)) {
            return;
        }

        $cases = [];
        $ids = [];

        collect($assignments)->each(function ($assignment) use (&$ids, &$cases) {
            $ids[] = $assignment->shiftId;
            $cases[] = "WHEN id = {$assignment->shiftId} THEN {$assignment->soldierId}";
        });

        Shift::whereIn('id', $ids)
            ->update(['soldier_id' => \DB::raw('CASE '.implode(' ', $cases).' END')]);
    }
}
