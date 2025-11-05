<?php

namespace App\Services;

use App\Enums\RecurringType;
use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;
use Carbon\Carbon;

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
        $this->maxNightsGreaterThanMaxShifts();
        $this->maxWeekendsGreaterThanCapacity();
        $this->weekendTasksNotPointed();
        $this->wrongTasksWeight();
        $this->soldiersWithQualificationsWithoutCapacity();
        $this->soldiersWithCapacityWithoutQualifications();
        $this->taskWithoutQualifiedSoldier();
        $this->shiftsWithoutEnoughAvailablesSoldiers();
        $this->soldiersWithCapacityWithoutAvailableTasks();

        return $this->data;
    }

    protected function maxNightsGreaterThanMaxShifts()
    {
        $maxNightsGreaterThanMaxShifts = collect();
        Soldier::whereColumn('max_shifts', '<', 'max_nights')
            ->get()
            ->groupBy('course')
            ->sortKeys()
            ->each(function ($soldiers, $course) use (&$maxNightsGreaterThanMaxShifts) {
                $courseNumber = $course ?: __('Unassigned course');

                $soldierNames = $soldiers
                    ->map(fn ($soldier) => $soldier->user?->displayName)
                    ->filter()
                    ->values();

                if ($soldierNames->isNotEmpty()) {
                    $maxNightsGreaterThanMaxShifts->push(
                        collect([__('Course').' '.$courseNumber])->merge($soldierNames)
                    );
                }
            });
        if ($maxNightsGreaterThanMaxShifts->isNotEmpty()) {
            $this->data->put(__('Soldiers whose maximum nights are greater than the maximum shifts'), $maxNightsGreaterThanMaxShifts);
        }
    }

    protected function maxWeekendsGreaterThanCapacity()
    {
        $maxWeekendsGreaterThanCapacity = collect();
        Soldier::whereColumn('capacity', '<', 'max_weekends')
            ->get()
            ->groupBy('course')
            ->sortKeys()
            ->each(function ($soldiers, $course) use (&$maxWeekendsGreaterThanCapacity) {
                $courseNumber = $course ?: __('Unassigned course');
                $soldierNames = $soldiers
                    ->map(fn ($soldier) => $soldier->user?->displayName)
                    ->filter()
                    ->values();
                if ($soldierNames->isNotEmpty()) {
                    $maxWeekendsGreaterThanCapacity->push(
                        collect([__('Course').' '.$courseNumber])->merge($soldierNames)
                    );
                }
            });
        if ($maxWeekendsGreaterThanCapacity->isNotEmpty()) {
            $this->data->put(__('Soldiers whose maximum weekends are greater than the capacity'), $maxWeekendsGreaterThanCapacity);
        }
    }

    protected function weekendTasksNotPointed()
    {
        $weekendTasksNotPointed = Task::where('kind', TaskKind::WEEKEND->value)
            ->where('parallel_weight', 0)
            ->pluck('name');
        if (collect($weekendTasksNotPointed)->isNotEmpty()) {
            $this->data->put(__('Weekend tasks not pointed'), $weekendTasksNotPointed);
        }
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

    protected function soldiersWithQualificationsWithoutCapacity()
    {
        $soldiersWithQualificationsWithoutCapacity = collect();
        Soldier::whereJsonLength('qualifications', '>', 0)
            ->whereNot(function ($query) {
                $query->where('max_shifts', '>', 0)
                    ->orWhere('max_nights', '>', 0)
                    ->orWhere('max_weekends', '>', 0)
                    ->orWhere('capacity', '>', 0)
                    ->orWhere('max_alerts', '>', 0)
                    ->orWhere('max_in_parallel', '>', 0);
            })
            ->get()
            ->groupBy('course')
            ->sortKeys()
            ->each(function ($soldiers, $course) use (&$soldiersWithQualificationsWithoutCapacity) {
                $courseNumber = $course ?: __('Unassigned course');
                $soldierNames = $soldiers
                    ->map(fn ($soldier) => $soldier->user?->displayName)
                    ->filter()
                    ->values();
                if ($soldierNames->isNotEmpty()) {
                    $soldiersWithQualificationsWithoutCapacity->push(
                        collect([__('Course').' '.$courseNumber])->merge($soldierNames)
                    );
                }
            });
        if (collect($soldiersWithQualificationsWithoutCapacity)->isNotEmpty()) {
            $this->data->put(__('Qualified soldiers unable to perform shifts'), $soldiersWithQualificationsWithoutCapacity);
        }
    }

    protected function soldiersWithCapacityWithoutQualifications()
    {
        $soldiersWithCapacityWithoutQualifications = collect();
        Soldier::where(function ($query) {
            $query->where('max_shifts', '>', 0)
                ->orWhere('max_nights', '>', 0)
                ->orWhere('max_weekends', '>', 0)
                ->orWhere('capacity', '>', 0)
                ->orWhere('max_alerts', '>', 0)
                ->orWhere('max_in_parallel', '>', 0);
        })
            ->whereJsonLength('qualifications', 0)
            ->get()
            ->groupBy('course')
            ->sortKeys()
            ->each(function ($soldiers, $course) use (&$soldiersWithCapacityWithoutQualifications) {
                $courseNumber = $course ?: __('Unassigned course');
                $soldierNames = $soldiers
                    ->map(fn ($soldier) => $soldier->user?->displayName)
                    ->filter()
                    ->values();
                if ($soldierNames->isNotEmpty()) {
                    $soldiersWithCapacityWithoutQualifications->push(
                        collect([__('Course').' '.$courseNumber])->merge($soldierNames)
                    );
                }
            });
        if (collect($soldiersWithCapacityWithoutQualifications)->isNotEmpty()) {
            $this->data->put(__('Soldiers with capacity without qualifications'), $soldiersWithCapacityWithoutQualifications);
        }
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

        $onTimeTasks->each(function ($type, $id) use (&$tasksTypes) {
            $shift = Shift::find($id);
            if ($shift && ! $tasksTypes->contains($type) && $shift->soldier_id !== null) {
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

    protected function analyzeShiftAvailability($shifts, $soldiers)
    {
        $result = collect();

        $shifts->groupBy('taskType')->each(function ($shiftsGroup, $taskType) use ($soldiers, &$result) {
            $this->analyzeTaskTypeAvailability($shiftsGroup, $taskType, $soldiers, $result);
        });

        return $result;
    }

    protected function analyzeTaskTypeAvailability($shifts, string $taskType, $soldiers, &$result)
    {
        $shiftsRequired = $this->shiftsRequired($shifts);
        $qualifiedSoldiers = $this->getQualifiedSoldiers($soldiers, $taskType);
        $soldiersAvailability = $this->soldiersAvailability($qualifiedSoldiers);

        $this->compareRequiredVsAvailable($shiftsRequired, $soldiersAvailability, $taskType, $result);
    }

    protected function getQualifiedSoldiers($soldiers, string $taskType)
    {
        return $soldiers->filter(fn (SoldierService $soldier) => $soldier->isQualified($taskType));
    }

    protected function compareRequiredVsAvailable($shiftsRequired, $soldiersAvailability, $taskType, &$result)
    {
        $shiftsRequired->each(function ($requiredCount, $kind) use ($soldiersAvailability, $taskType, &$result) {
            $availableCount = $soldiersAvailability[$kind] ?? 0;

            if ($requiredCount > 0 && $requiredCount > $availableCount) {
                $result->push(__('Required VS Available Sentence', [
                    'type' => $taskType,
                    'kind' => __($kind),
                    'required' => $requiredCount,
                    'available' => $availableCount,
                ]));
            }
        });
    }

    protected function getSoldiersDetails()
    {
        $range = new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth());

        $soldiersCollection = collect();

        Soldier::where('is_reservist', false)
            ->with([
                'shifts' => fn ($q) => $q->whereBetween('start_date', [$range->start, $range->end]),
            ])
            ->chunk(200, function ($soldiers) use (&$soldiersCollection) {
                $processed = $soldiers->map(function (Soldier $soldier) {
                    $shifts = Helpers::mapSoldierShifts($soldier->shifts, false);
                    $concurrentsShifts = Helpers::mapSoldierShifts($soldier->shifts, true);
                    $capacityHold = Helpers::capacityHold($shifts, $concurrentsShifts);

                    return Helpers::buildSoldier($soldier, [], [], $capacityHold, $concurrentsShifts);
                })->filter(fn ($soldier) => $soldier->hasMaxes());

                $soldiersCollection = $soldiersCollection->merge($processed);
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
            ->chunk(300, function ($shifts) use (&$shiftsCollection) {
                $processed = $shifts->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));
                $shiftsCollection = $shiftsCollection->merge($processed);
            });

        return $shiftsCollection;
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

    protected function soldiersWithCapacityWithoutAvailableTasks()
    {
        $sumByTaskTypes = $this->calculateShiftsSumByTaskTypes();

        $maxTypes = collect([
            'max_shifts' => 'Regulars',
            'max_nights' => 'Nights',
            'max_weekends' => 'Weekends',
            'max_alerts' => 'Alerts',
            'max_in_parallel' => 'In parallels',
            'capacity' => 'Points',
        ]);

        $soldiersByType = $this->initializeSoldiersByType();

        $soldiersByType = $this->fillSoldiersByType($maxTypes, $sumByTaskTypes, $soldiersByType);

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
                ->whereHas('task', fn ($q) => $q->where('type', $type));

            $sum = fn ($kinds) => (clone $baseQuery)
                ->whereHas('task', fn ($q) => $q->whereIn('kind', (array) $kinds))
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
            'max_shifts' => collect([__('Max shifts').': ']),
            'max_nights' => collect([__('Max nights').': ']),
            'max_weekends' => collect([__('Max weekends').': ']),
            'max_alerts' => collect([__('Max alerts').': ']),
            'max_in_parallel' => collect([__('Max in parallel').': ']),
            'capacity' => collect([__('Capacity').': ']),
        ]);
    }

    protected function fillSoldiersByType($maxTypes, $sumByTaskTypes, $soldiersByType)
    {
        $maxTypes->each(function ($type, $max) use (&$soldiersByType, $sumByTaskTypes) {
            $soldiers = Soldier::where($max, '>', 0)->pluck('qualifications', 'id');

            collect($soldiers)->each(function ($qualifications, $id) use (&$soldiersByType, $type, $max, $sumByTaskTypes) {
                $sum = $this->calculateSoldierSum($id, $qualifications, $type, $sumByTaskTypes);

                if (collect($qualifications)->isNotEmpty() && $sum == 0) {
                    $soldiersByType[$max]->push(User::where('userable_id', $id)->first()->displayName);
                }
            });
        });

        return $soldiersByType;
    }

    protected function calculateSoldierSum($id, $qualifications, $type, $sumByTaskTypes)
    {
        $sum = 0;

        collect($qualifications)->each(function ($qualification) use ($id, $type, &$sum, $sumByTaskTypes) {
            $sum += $sumByTaskTypes->get($qualification, collect())->get($type, 0);

            $soldier = Soldier::find($id);

            if ($type == 'Regulars' && $soldier->max_nights > 0) {
                $sum += $sumByTaskTypes->get($qualification, collect())->get('Nights', 0);
            }

            if ($type == 'Nights' && $soldier->max_shifts == 0) {
                $sum = 0;
            }
        });

        return $sum;
    }
}

// protected function tasksWithoutEnoughAvailablesSoldiers()
// {
//     $tasksWithoutEnoughAvailablesSoldiers = collect();
//     $taskTypes = Task::select('type')
//         ->distinct()
//         ->orderBy('type')
//         ->pluck('type')
//         ->all();
//     collect($taskTypes)->each(function ($type) use (&$tasksWithoutEnoughAvailablesSoldiers) {
//         $regularsCount = $this->shiftQuery($type, TaskKind::REGULAR->value)->count();
//         $alertsCount = $this->shiftQuery($type, TaskKind::ALERT->value)->count();
//         $nightsCount = $this->shiftQuery($type, TaskKind::NIGHT->value)->count();
//         $weekendsSum =  $this->shiftQuery($type, TaskKind::WEEKEND->value)
//             ->get()
//             ->sum(fn($shift) => $shift->parallel_weight > 0 ? $shift->parallel_weight : $shift->task->parallel_weight);
//         $points = Shift::whereBetween('start_date', [$this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()])
//             ->whereHas('task', function ($query) use ($type) {
//                 $query->where('type', $type);
//             })
//             ->where(function ($query) {
//                 $query->where('parallel_weight', '>', 0)
//                     ->orWhere(function ($query) {
//                         $query->whereHas('task', function ($query) {
//                             $query->where('parallel_weight', '>', 0);
//                         });
//                     });
//             })
//             ->get()
//             ->sum(fn($shift) => $shift->parallel_weight > 0 ? $shift->parallel_weight : $shift->task->parallel_weight);

//         $soldierQuery = Soldier::whereJsonContains('qualifications', $type);
//         $regulars = $soldierQuery->sum('max_shifts');
//         $alerts = $soldierQuery->sum('max_alerts');
//         $nights = $soldierQuery->sum('max_nights');
//         $weekends = $soldierQuery->sum('max_weekends');
//         $capacity = $soldierQuery->sum('capacity');
//         if ($regularsCount > $regulars) {
//             $tasksWithoutEnoughAvailablesSoldiers->push($this->requiredVSAvailableSentence($type, __('Regulars'), $regularsCount, $regulars));
//         }
//         if ($alertsCount > $alerts) {
//             $tasksWithoutEnoughAvailablesSoldiers->push($this->requiredVSAvailableSentence($type, __('Alerts'), $alertsCount, $alerts));
//         }
//         if ($nightsCount > $nights) {
//             $tasksWithoutEnoughAvailablesSoldiers->push($this->requiredVSAvailableSentence($type, __('Nights'), $nightsCount, $nights));
//         }
//         if ($weekendsSum > $weekends) {
//             $tasksWithoutEnoughAvailablesSoldiers->push($this->requiredVSAvailableSentence($type, __('Weekends'), $weekendsSum, $weekends));
//         }
//         if ($points > $capacity) {
//             $tasksWithoutEnoughAvailablesSoldiers->push($this->requiredVSAvailableSentence($type, __('Points'), $points, $capacity));
//         }
//     });
//     $this->data->put(__('Tasks without enough available soldiers'), $tasksWithoutEnoughAvailablesSoldiers);
// }
// if($sum > 0 &&  $soldiersAvailability[$kind] < 0){
//     \Log::info(json_encode(['taskType', $taskType]));
//     \Log::info(json_encode(['Result', $sum / $soldiersAvailability[$kind]]));

// }
// if (  $sum > 0 &&($soldiersAvailability[$kind]  == 0  || ($sum / $soldiersAvailability[$kind] > 0.75))) {
