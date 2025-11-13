<?php

namespace App\Services;

use App\Enums\RecurringType;
use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class PredictingProblems
{
    protected $date;

    protected $data;

    public function __construct($date)
    {
        $this->date = Carbon::parse($date);
        $this->data = collect();
    }

    public function getData()
    {
        set_time_limit(seconds: 0);
        $this->maxNightsGreaterThanMaxShifts();
        $this->maxWeekendsGreaterThanCapacity();
        $this->weekendShiftsNotPointed();
        $this->wrongTasksWeight();
        $this->wrongShiftsWeight();
        $this->soldiersWithQualificationsWithoutCapacity();
        $this->soldiersWithCapacityWithoutQualifications();
        $this->taskWithoutQualifiedSoldier();
        $this->shiftsWithoutEnoughAvailablesSoldiers();
        $this->soldiersWithCapacityWithoutAvailableShifts();

        return $this->data;
    }

    protected function maxNightsGreaterThanMaxShifts()
    {
        $this->compareSoldierColumns(
            'max_shifts',
            'max_nights',
            'Soldiers whose maximum nights are greater than the maximum shifts'
        );
    }

    protected function maxWeekendsGreaterThanCapacity()
    {
        $this->compareSoldierColumns(
            'capacity',
            'max_weekends',
            'Soldiers whose maximum weekends are greater than the capacity'
        );
    }

    protected function compareSoldierColumns($columnLeft, $columnRight, $title)
    {
        $this->collectSoldiersByCondition(
            fn ($query): mixed => $query->whereColumn($columnLeft, '<', $columnRight),
            $title
        );
    }

    protected function baseWeekendQuery($isWeekendCondition)
    {
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();

        $query = Shift::whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->join('tasks', 'tasks.id', '=', 'shifts.task_id')
            ->where('tasks.parallel_weight', 0)
            ->where(function ($query) {
                $query->where('shifts.parallel_weight', 0)
                    ->orWhereNull('shifts.parallel_weight');
            })
            ->select('tasks.type', 'tasks.name', 'shifts.start_date', 'shifts.end_date');

        if ($isWeekendCondition) {
            $query->where('shifts.is_weekend', true);
        } else {
            $query->where('shifts.is_weekend', '!=', true)
                ->where('tasks.kind', TaskKind::WEEKEND->value);
        }

        return $query->get()->groupBy('type')->sortKeys();
    }

    protected function weekendShiftsNotPointed()
    {
        $firstGroup = $this->baseWeekendQuery(true);
        $secondGroup = $this->baseWeekendQuery(false);

        $allGroups = $firstGroup->mergeRecursive($secondGroup);
        $weekendShiftsNotPointed = collect();

        $allGroups->each(function ($shifts, $type) use (&$weekendShiftsNotPointed) {
            $typeName = $type ?: __('Unassigned type');
            $shiftNames = $shifts->map(fn ($shift) => $this->formatShift($shift));
            if ($shiftNames->isNotEmpty()) {
                $weekendShiftsNotPointed->push(
                    collect([__('Task type').': '.$typeName])->merge($shiftNames)
                );
            }
        });

        if ($weekendShiftsNotPointed->isNotEmpty()) {
            $this->data->put(__('Weekend shifts not pointed'), $weekendShiftsNotPointed);
        }
    }

    protected function formatShift($shift)
    {
        $hasHebrew = preg_match('/\p{Hebrew}/u', $shift->name);

        $nameDirection = $hasHebrew ? 'rtl' : 'ltr';

        return new HtmlString(sprintf(
            '<span dir="auto"><span dir="%s">%s</span> (%s → %s)</span>',
            $nameDirection,
            e($shift->name),
            e($shift->start_date),
            e($shift->end_date)
        ));
    }

    protected function wrongTasksWeight()
    {
        $wrongTasksWeight = Task::where(function ($query) {
            $query->where('parallel_weight', '!=', 0)
                ->where('parallel_weight', '!=', 50)
                ->where('parallel_weight', '!=', 100)
                ->where('parallel_weight', '!=', 200);
        })
            ->pluck('name');
        if (collect($wrongTasksWeight)->isNotEmpty()) {
            $this->data->put(__('Tasks with incorrect points'), $wrongTasksWeight);
        }
    }

    protected function wrongShiftsWeight()
    {
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();
        $wrongShiftsWeight = collect();
        $shifts = Shift::whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->whereNotNull('shifts.parallel_weight')
            ->where(function ($query) {
                $query->where('shifts.parallel_weight', '!=', 0)
                    ->where('shifts.parallel_weight', '!=', 50)
                    ->where('shifts.parallel_weight', '!=', 100)
                    ->where('shifts.parallel_weight', '!=', 200);
            })
            ->join('tasks', 'tasks.id', '=', 'shifts.task_id')
            ->select('tasks.type', 'tasks.name', 'shifts.start_date', 'shifts.end_date')
            ->get()
            ->groupBy('type')
            ->sortKeys();
        $shifts->each(function ($shifts, $type) use (&$wrongShiftsWeight) {
            $typeName = $type ?: __('Unassigned type');
            $shiftNames = $shifts->map(fn ($shift) => $this->formatShift($shift));
            if ($shiftNames->isNotEmpty()) {
                $wrongShiftsWeight->push(
                    collect([__('Task type').': '.$typeName])->merge($shiftNames)
                );
            }
        });
        if (collect($wrongShiftsWeight)->isNotEmpty()) {
            $this->data->put(__('Shifts with incorrect points'), $wrongShiftsWeight);
        }
    }

    protected function collectSoldiersByCondition($queryCallback, $title): void
    {
        $collection = collect();

        Soldier::query()
            ->tap($queryCallback)
            ->with('user')
            ->get(['id', 'course'])
            ->groupBy('course')
            ->sortKeys()
            ->each(function ($soldiers, $course) use (&$collection) {
                $courseNumber = $course ?: __('Unassigned course');

                $soldierNames = $soldiers
                    ->map(fn ($soldier) => $soldier->user?->displayName)
                    ->filter()
                    ->values();

                if ($soldierNames->isNotEmpty()) {
                    $collection->push(
                        collect([__('Course').' '.$courseNumber])->merge($soldierNames)
                    );
                }
            });

        if ($collection->isNotEmpty()) {
            $this->data->put(__($title), $collection);
        }
    }

    protected function soldiersWithQualificationsWithoutCapacity()
    {
        $this->collectSoldiersByCondition(
            fn ($query) => $query
                ->whereJsonLength('qualifications', '>', 0)
                ->whereNot(function ($q) {
                    $q->where('max_shifts', '>', 0)
                        ->orWhere('max_nights', '>', 0)
                        ->orWhere('max_weekends', '>', 0)
                        ->orWhere('capacity', '>', 0)
                        ->orWhere('max_alerts', '>', 0)
                        ->orWhere('max_in_parallel', '>', 0);
                }),
            __('Qualified soldiers unable to perform shifts')
        );
    }

    protected function soldiersWithCapacityWithoutQualifications()
    {
        $this->collectSoldiersByCondition(
            fn ($query) => $query
                ->where(function ($q) {
                    $q->where('max_shifts', '>', 0)
                        ->orWhere('max_nights', '>', 0)
                        ->orWhere('max_weekends', '>', 0)
                        ->orWhere('capacity', '>', 0)
                        ->orWhere('max_alerts', '>', 0)
                        ->orWhere('max_in_parallel', '>', 0);
                })
                ->whereJsonLength('qualifications', 0),
            __('Soldiers with capacity without qualifications')
        );
    }

    protected function taskWithoutQualifiedSoldier()
    {
        $tasksTypes = $this->getRelevantTaskTypes();
        $tasksTypes = $this->includeOneTimeTasksWithSoldier($tasksTypes);
        $taskWithoutQualifiedSoldier = $this->findTasksWithoutQualifiedSoldiers($tasksTypes);
        if ($taskWithoutQualifiedSoldier->isNotEmpty()) {
            $this->data->put(__('Tasks without qualified soldiers'), $taskWithoutQualifiedSoldier);
        }
    }

    protected function getRelevantTaskTypes()
    {
        return Task::where('recurring->type', '!=', RecurringType::ONETIME->value)
            ->pluck('type')
            ->unique();
    }

    protected function includeOneTimeTasksWithSoldier($tasksTypes)
    {
        $onTimeTasks = Task::where('recurring->type', RecurringType::ONETIME->value)
            ->pluck('type', 'id');
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();
        $onTimeTasks->each(function ($type, $id) use (&$tasksTypes, $startOfMonth, $endOfMonth) {
            $shift = Shift::whereBetween('start_date', [$startOfMonth, $endOfMonth])
                ->where('task_id', $id)
                ->get()
                ->first();
            if ($shift && ! $tasksTypes->contains($type) && $shift->soldier_id == null) {
                $tasksTypes->push($type);
            }
        });

        return $tasksTypes;
    }

    protected function findTasksWithoutQualifiedSoldiers($tasksTypes)
    {
        $result = collect();

        $tasksTypes->each(function ($type) use (&$result) {
            $soldiersCount = Soldier::whereJsonContains('qualifications', $type)->count();
            if ($soldiersCount === 0) {
                $result->push($type);
            }
        });

        return $result;
    }

    protected function shiftsWithoutEnoughAvailablesSoldiers()
    {
        $soldiers = $this->getSoldiersDetails();
        $shifts = $this->getShiftWithTasks();

        $shiftsWithoutEnoughAvailablesSoldiers = $this->analyzeShiftAvailability($shifts, $soldiers);

        if ($shiftsWithoutEnoughAvailablesSoldiers->isNotEmpty()) {
            $this->data->put(__('Tasks without enough available soldiers'), $shiftsWithoutEnoughAvailablesSoldiers);
        }
    }

    protected function getSoldiersDetails()
    {
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();

        $soldiersQuery = Soldier::where('is_reservist', false)
            ->with([
                'user',
                'shifts' => fn ($q) => $q->whereBetween('start_date', [$startOfMonth, $endOfMonth]),
            ]);

        $soldiersCollection = collect();

        $soldiersQuery->chunkById(200, function ($soldiersChunk) use (&$soldiersCollection) {
            foreach ($soldiersChunk as $soldier) {
                $mappedShifts = Helpers::mapSoldierShifts($soldier->shifts, false);
                $mappedConcurrent = Helpers::mapSoldierShifts($soldier->shifts, true);

                $capacityHold = Helpers::capacityHold($mappedShifts, $mappedConcurrent);

                $builtSoldier = Helpers::buildSoldier(
                    $soldier,
                    [],
                    [],
                    $capacityHold,
                    $mappedConcurrent
                );

                if ($builtSoldier->hasMaxes()) {
                    $soldiersCollection->push($builtSoldier);
                }

                unset($soldier->shifts);
                unset($soldier);
            }

            gc_collect_cycles();
        });

        return $soldiersCollection;
    }

    protected function getShiftWithTasks()
    {
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();

        $shiftsCollection = collect();

        Shift::query()
            ->with('task')
            ->whereNull('soldier_id')
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->orderBy('id')
            ->chunkById(200, function ($shifts) use (&$shiftsCollection) {
                foreach ($shifts as $shift) {
                    $built = Helpers::buildShift($shift);
                    if ($built) {
                        $shiftsCollection->push($built);
                    }

                    unset($shift->task);
                    unset($shift);
                }

                gc_collect_cycles();
            });

        return $shiftsCollection;
    }

    protected function analyzeShiftAvailability($shifts, $soldiers)
    {
        $result = collect();

        $shifts->groupBy('taskType')->each(function ($shiftsGroup, $taskType) use ($soldiers, &$result) {
            $this->analyzeTaskTypeAvailability($shiftsGroup, $taskType, $soldiers, $result);
        });

        return $result;
    }

    protected function analyzeTaskTypeAvailability($shifts, $taskType, $soldiers, &$result)
    {
        $shiftsRequired = $this->shiftsRequired($shifts);
        $qualifiedSoldiers = $this->getQualifiedSoldiers($soldiers, $taskType);
        $soldiersAvailability = $this->soldiersAvailability($qualifiedSoldiers);

        $this->compareRequiredVsAvailable($shiftsRequired, $soldiersAvailability, $taskType, $result);
    }

    protected function shiftsRequired($shifts)
    {
        return collect([
            'Regulars' => $this->shiftFilter($shifts, TaskKind::REGULAR->value)->count(),
            'Alerts' => $this->shiftFilter($shifts, TaskKind::ALERT->value)->count(),
            'Nights' => $this->shiftFilter($shifts, TaskKind::NIGHT->value)->count(),
            'In parallels' => $this->shiftFilter($shifts, TaskKind::INPARALLEL->value)->count(),
            'Weekends' => $this->shiftFilter($shifts, TaskKind::WEEKEND->value)
                ->sum(fn (ShiftService $shift) => $shift->points),
            'Points' => $shifts->sum(fn (ShiftService $shift) => $shift->points),
        ]);
    }

    protected function shiftFilter($shifts, $kind)
    {
        return $shifts->filter(fn (ShiftService $shift) => $shift->kind == $kind);
    }

    protected function getQualifiedSoldiers($soldiers, string $taskType)
    {
        return $soldiers->filter(fn (SoldierService $soldier) => $soldier->isQualified($taskType));
    }

    protected function soldiersAvailability($soldiers)
    {
        return collect([
            'Regulars' => $soldiers->sum(fn (SoldierService $soldier) => $soldier->shiftsMaxData->remaining()),
            'Alerts' => $soldiers->sum(fn (SoldierService $soldier) => $soldier->alertsMaxData->remaining()),
            'Nights' => $soldiers->sum(fn (SoldierService $soldier) => $soldier->nightsMaxData->remaining()),
            'In parallels' => $soldiers->sum(fn (SoldierService $soldier) => $soldier->inParallelMaxData->remaining()),
            'Weekends' => $soldiers->sum(fn (SoldierService $soldier) => $soldier->weekendsMaxData->remaining()),
            'Points' => $soldiers->sum(fn (SoldierService $soldier) => $soldier->pointsMaxData->remaining()),
        ]);
    }

    protected function compareRequiredVsAvailable($shiftsRequired, $soldiersAvailability, $taskType, &$result)
    {
        $taskTypeHtml = new HtmlString('<strong>'.e($taskType).'</strong>');
        $shiftsRequired->each(function ($requiredCount, $kind) use ($soldiersAvailability, $taskTypeHtml, &$result) {
            $availableCount = $soldiersAvailability[$kind] ?? 0;
            $kind = new HtmlString('<em>'.e(__($kind)).'</em>');
            $required = new HtmlString('<strong>'.e($requiredCount).'</strong>');
            $available = new HtmlString('<strong>'.e($availableCount).'</strong>');
            if ($requiredCount > 0 && $requiredCount > $availableCount) {
                $result->push(new HtmlString(
                    __('Required VS Available Sentence', [
                        'type' => $taskTypeHtml,
                        'kind' => $kind,
                        'required' => $required,
                        'available' => $available,
                    ])
                ));
            }
        });
    }

    protected function soldiersWithCapacityWithoutAvailableShifts()
    {
        $sumByTaskTypes = $this->calculateShiftsSumByTaskTypes();
        $soldiersByType = $this->initializeSoldiersByType();
        $soldiersByType = $this->fillSoldiersByType($sumByTaskTypes, $soldiersByType);
        $soldiersByType = $soldiersByType->filter(fn ($names) => $names->count() > 1);
        $this->data->put(
            __('Soldiers with abilities and authority without appropriate shifts'),
            $soldiersByType->map(fn ($names) => $names->map(fn ($name) => json_decode(json_encode($name, JSON_UNESCAPED_UNICODE))))
        );
    }

    protected function calculateShiftsSumByTaskTypes()
    {
        $sumByTaskTypes = collect();
        $taskTypes = Task::distinct()->orderBy('type')->pluck('type');

        collect($taskTypes)->each(function ($type) use (&$sumByTaskTypes) {
            $baseQuery = Shift::whereNull('soldier_id')
                ->whereBetween('start_date', [$this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()])
                ->whereHas('task', fn ($query) => $query->where('type', $type));

            $sum = fn ($kinds) => (clone $baseQuery)
                ->whereHas('task', fn ($query) => $query->whereIn('kind', (array) $kinds))
                ->count();

            $sumByTaskTypes[$type] = collect([
                'Regulars' => $sum(TaskKind::REGULAR->value),
                'Alerts' => $sum(TaskKind::ALERT->value),
                'Nights' => $sum(TaskKind::NIGHT->value),
                'In parallels' => $sum(TaskKind::INPARALLEL->value),
                'Weekends' => $this->sumWeekendWeight($baseQuery),
                'Points' => $this->sumPointsWeight($baseQuery),
            ]);
        });

        return $sumByTaskTypes;
    }

    protected function sumWeekendWeight($baseQuery)
    {
        return (clone $baseQuery)
            ->whereHas('task', fn ($q) => $q->where('kind', TaskKind::WEEKEND->value))
            ->selectRaw('
                SUM(COALESCE(parallel_weight,
                    (SELECT parallel_weight FROM tasks WHERE tasks.id = shifts.task_id)
                )) as total
            ')
            ->value('total');
    }

    protected function sumPointsWeight($baseQuery)
    {
        return (clone $baseQuery)
            ->selectRaw('
                SUM(COALESCE(parallel_weight,
                    (SELECT parallel_weight FROM tasks WHERE tasks.id = shifts.task_id)
                )) as total
            ')
            ->value('total');
    }

    protected function initializeSoldiersByType()
    {
        return collect([
            'max_shifts' => __('Max shifts'),
            'max_nights' => __('Max nights'),
            'max_weekends' => __('Max weekends'),
            'max_alerts' => __('Max alerts'),
            'max_in_parallel' => __('Max in parallel'),
            'capacity' => __('Capacity'),
        ])->map(fn ($label) => collect([$label.':']));
    }

    protected function fillSoldiersByType($sumByTaskTypes, $soldiersByType)
    {
        $maxTypes = collect([
            'max_shifts' => 'Regulars',
            'max_nights' => 'Nights',
            'max_weekends' => 'Weekends',
            'max_alerts' => 'Alerts',
            'max_in_parallel' => 'In parallels',
            'capacity' => 'Points',
        ]);

        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();

        $maxTypes->each(function ($type, $max) use (&$soldiersByType, $sumByTaskTypes, $startOfMonth, $endOfMonth) {
            $soldiersQuery = Soldier::where($max, '>', 0)
                ->with([
                    'user',
                    'shifts' => fn ($q) => $q->whereBetween('start_date', [$startOfMonth, $endOfMonth]),
                ]);

            $soldiersQuery->chunkById(200, function ($soldiersChunk) use (&$soldiersByType, $sumByTaskTypes, $type, $max) {
                foreach ($soldiersChunk as $soldier) {
                    $shifts = Helpers::mapSoldierShifts($soldier->shifts, false);
                    $concurrentsShifts = Helpers::mapSoldierShifts($soldier->shifts, true);
                    $capacityHold = Helpers::capacityHold($shifts, $concurrentsShifts);
                    $builtSoldier = Helpers::buildSoldier(
                        $soldier,
                        [],
                        [],
                        $capacityHold,
                        $concurrentsShifts
                    );
                    $builtSoldier->userName = $soldier->user?->displayName ?? __('Unknown soldier');

                    if (
                        $this->usedGreaterThenZero($builtSoldier, $max)
                        && collect($builtSoldier->qualifications)->isNotEmpty()
                    ) {
                        $sum = $this->calculateSoldierSum($builtSoldier, $type, $sumByTaskTypes);

                        if ($sum == 0) {
                            $soldiersByType[$max]->push($builtSoldier->userName);
                        }
                    }

                    unset($soldier->shifts, $soldier);
                }

                gc_collect_cycles();
            });
        });

        return $soldiersByType;
    }

    protected function usedGreaterThenZero(SoldierService $soldier, $max)
    {
        $maxName = match ($max) {
            'max_shifts' => 'shiftsMaxData',
            'max_nights' => 'nightsMaxData',
            'max_weekends' => 'weekendsMaxData',
            'max_alerts' => 'alertsMaxData',
            'max_in_parallel' => 'inParallelMaxData',
            'capacity' => 'pointsMaxData',
        };

        return $soldier->{$maxName}->remaining() > 0;
    }

    protected function calculateSoldierSum(SoldierService $soldier, $type, $sumByTaskTypes)
    {
        $sum = 0;

        collect($soldier->qualifications)->each(function ($qualification) use ($soldier, $type, &$sum, $sumByTaskTypes) {
            $sum += $sumByTaskTypes->get($qualification, collect())->get($type, 0);

            if ($type == 'Regulars' && $soldier->nightsMaxData->used > 0) {
                $sum += $sumByTaskTypes->get($qualification, collect())->get('Nights', 0);
            }

            if ($type == 'Nights' && $soldier->shiftsMaxData->used == 0) {
                $sum = 0;
            }
        });

        return $sum;
    }
}
