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
        $task = $shift->task ?? $shift->task()->withTrashed()->first();

        return new ShiftService(
            $shift->id,
            $shift->task_id,
            $task->type,
            $shift->start_date,
            $shift->end_date,
            $shift->parallel_weight === null ? $task->parallel_weight : $shift->parallel_weight,
            self::kind($shift, $task),
            $task->concurrent_tasks
        );
    }

    protected static function kind(Shift $shift, $task)
    {
        if ($shift->is_weekend === true) {
            return TaskKind::WEEKEND->value;
        }
        if ($shift->is_weekend === false && $task->kind === TaskKind::WEEKEND->value) {
            return TaskKind::REGULAR->value;
        }

        return $task->kind;
    }

    public static function buildSoldier($soldier, $constraints, $shifts, array $capacityHold, $concurrentsShifts = []): SoldierService
    {
        return new SoldierService(
            $soldier->id,
            $soldier->course,
            new MaxData($soldier->capacity, $capacityHold['points'] ?? 0),
            new MaxData($soldier->max_shifts, $capacityHold['regulars'] ?? 0),
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

    public static function buildConstraints($constraints)
    {
        return $constraints
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
        $regulars = 0;
        $alerts = 0;
        $inParallel = 0;
        collect($shifts)
            ->filter(fn (ShiftService $shift) => $shift->id != 0)
            ->each(function (ShiftService $shift) use (&$regulars, &$points, &$nights, &$weekends, &$alerts, &$inParallel) {
                $points += $shift->points;
                match ($shift->kind) {
                    TaskKind::WEEKEND->value => $weekends += $shift->points,
                    TaskKind::NIGHT->value => [$nights++, $regulars++],
                    TaskKind::ALERT->value => $alerts++,
                    TaskKind::INPARALLEL->value => $inParallel++,
                    TaskKind::REGULAR->value => $regulars++,
                };
            });

        return [
            'regulars' => $regulars,
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
            $spaces = $shift->getShiftSpaces($shifts);
            if (! empty($spaces)) {
                collect($spaces)->map(fn (Range $space) => $allSpaces->push(new ShiftService(0, 0, '', $space->start, $space->end, 0, TaskKind::REGULAR->value, [])));
            }
        });

        return $allSpaces;
    }

    public static function getSoldiersShifts($soldierId, $range, $inParallel)
    {
        return Shift::where('soldier_id', $soldierId)
            ->whereHas('task', function ($query) use ($inParallel) {
                $query->withTrashed()
                    ->when($inParallel, fn ($query) => $query->where('kind', TaskKind::INPARALLEL->value))
                    ->when(! $inParallel, fn ($query) => $query->where('kind', '!=', TaskKind::INPARALLEL->value));
            })
            ->where(function ($query) use ($range) {
                $query->where('start_date', '<=', $range->end)
                    ->where('start_date', '>=', $range->start);
            })
            ->get()
            ->map(fn (Shift $shift): ShiftService => self::buildShift($shift));
    }

    public static function getConstraintBy(int $soldierId, $newRange)
    {
        $constraints = Constraint::where('soldier_id', $soldierId)
            ->whereBetween('start_date', [$newRange->start, $newRange->end])
            ->get();

        return self::buildConstraints($constraints);
    }

    public static function addPrevMonthSpaces(int $soldierId, $date)
    {
        $lastDay = $date->copy()->startOfMonth()->subDay();
        $lastMonthShifts = self::getLastDayOfLastMonthShifts($soldierId, $lastDay);

        return self::addShiftsSpaces($lastMonthShifts);
    }

    protected static function getLastDayOfLastMonthShifts($soldierId, $lastDay)
    {
        return Shift::where('soldier_id', $soldierId)
            ->where(function ($query) use ($lastDay) {
                $query->whereDate('start_date', $lastDay)
                    ->orWhereDate('end_date', $lastDay);
            })
            ->whereHas('task', function ($query) {
                $query->withTrashed()->where('kind', '!=', TaskKind::INPARALLEL->value);
            })
            ->lazy()
            ->map(fn (Shift $shift): ShiftService => self::buildShift($shift));
    }

    public static function updateShiftTable($assignments)
    {
        if (empty($assignments)) {
            return;
        }

        $chunks = array_chunk($assignments->toArray(), 80);

        foreach ($chunks as $chunk) {
            $cases = [];
            $ids = [];

            collect($chunk)->each(function ($assignment) use (&$ids, &$cases) {
                $ids[] = $assignment->shiftId;
                $cases[] = "WHEN id = {$assignment->shiftId} THEN {$assignment->soldierId}";
            });

            Shift::whereIn('id', $ids)
                ->update(['soldier_id' => \DB::raw('CASE '.implode(' ', $cases).' END')]);
        }
    }
}
