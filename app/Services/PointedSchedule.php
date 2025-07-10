<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\DaysInWeek;
use App\Enums\TaskKind;

class PointedSchedule
{
    protected $soldiers;

    protected $shifts;

    protected $courses;

    protected $shiftsData;

    protected $soldiersDict;

    protected $rotationAssignments;

    protected $assignments;

    protected $shiftDumbbells;

    protected $minimalRotationSize;

    protected $maximalRotationSize;

    protected $allowedGap;

    public function __construct($soldiers, $shifts)
    {
        $this->soldiers = collect($soldiers);
        $this->shifts = collect($shifts);
        $this->courses = collect([]);
        $this->soldiersDict = collect([]);
        $this->shiftsData = collect([]);
        $this->rotationAssignments = collect([]);
        $this->assignments = collect([]);
        $this->shiftDumbbells = collect([
            'pointsRatio' => 0.4,
            'shiftsRatio' => 0.15,
            'weekendsRatio' => 0.3,
            'nightsRatio' => 0.15,
            'alertsRatio' => 0.15,
            'shiftAvailability' => 0.23,
            'shiftPointRatio' => 0.05,
            'isSingleShift' => 0.3,
            'isFullWeekend' => 0.7,
        ]);
        $this->minimalRotationSize = 0.5;
        $this->maximalRotationSize = 2;
        $this->allowedGap = 1;
    }

    public function schedule()
    {
        $this->initSoldiersDict();
        $this->buildShifts();
        $this->initCourses();
        $this->assign();

        return $this->assignments;
    }

    protected function initSoldiersDict()
    {
        $this->soldiers->each(function (Soldier $soldier) {
            $this->soldiersDict[$soldier->id] = 0;
        });
    }

    protected function buildShifts()
    {
        $this->attachWeekends();
        $this->initShiftsData();
    }

    protected function attachWeekends()
    {
        $fridays = $this->filterShiftsByDay(DaysInWeek::FRIDAY->value);
        $saturdays = $this->filterShiftsByDay(DaysInWeek::SATURDAY->value)->keyBy('id');

        $fridays->each(function (Shift $friday) use (&$saturdays) {
            $saturday = $saturdays->first(fn (Shift $saturdayShift): bool => $saturdayShift->taskId == $friday->taskId
                && $saturdayShift->range->start->copy()->isSameDay($friday->range->start->copy()->addDay())
                && ! $saturdayShift->range->isConflict($friday->range));
            if ($saturday) {
                $this->shifts->push(new AttachedWeekends($friday, $saturday));
            }
        });
    }

    protected function filterShiftsByDay(string $day)
    {
        return $this->shifts->filter(fn (Shift $shift): bool => $shift->kind == TaskKind::WEEKEND->value
            && ($shift->range->start->copy())->englishDayOfWeek == $day
            && round($shift->points, 2) == 1.00);
    }

    protected function initShiftsData(): void
    {
        $groupedShifts = collect($this->shifts)->groupBy('taskType');
        $groupedShifts->each(function ($shifts, $taskType) {
            $this->addShiftsDataByTask($taskType, collect($shifts));
        });
        $this->shiftsData = $this->getSortedShiftsList();
    }

    protected function addShiftsDataByTask(string $taskType, $shifts): void
    {
        $soldiers = $this->soldiers->filter(fn (Soldier $soldier) => $soldier->isQualified($taskType));
        $taskWeight = $this->getTaskWeight($shifts, $soldiers);
        $shifts->each(fn ($shift) => $this->addShiftData($shift, $soldiers, $taskWeight));
    }

    protected function getTaskWeight($shifts, $soldiers): array
    {
        $types = collect([
            'nights',
            'shifts',
            'alerts',
            'weekends',
            'points',
        ]);
        $required = $this->getRequired($shifts);
        $availables = $this->getAvailables($soldiers, $types);

        $weight = $types->mapWithKeys(fn ($type) => [$type.'Ratio' => $this->getRatio($required[$type], $availables[$type])]);
        $weight->mapWithKeys(fn ($value, $key) => [$key => $value * $this->shiftDumbbells[$key]]);

        return $weight->all();
    }

    protected function getRequired($shifts)
    {
        return collect([
            'points' => collect($shifts)->sum(callback: 'points'),
            'weekends' => collect($shifts)
                ->filter(fn (Shift $shift) => ($shift->kind == TaskKind::WEEKEND->value))
                ->sum(fn (Shift $shift) => $shift->points),
            'nights' => collect($shifts)->filter(fn (Shift $shift) => ($shift->kind == TaskKind::NIGHT->value))->count(),
            'alerts' => collect($shifts)->filter(fn (Shift $shift) => $shift->kind == TaskKind::ALERT->value)->count(),
            'shifts' => collect($shifts)
                ->filter(fn (Shift $shift) => ($shift->kind == TaskKind::NIGHT->value) || ($shift->kind == TaskKind::REGULAR->value))
                ->count(),
        ]);
    }

    protected function getAvailables($soldiers, $types)
    {
        return $types->mapWithKeys(fn ($type) => [$type => collect($soldiers)->sum(fn (Soldier $soldier) => $soldier->{$type.'MaxData'}->remaining())]);
    }

    protected function addShiftData(Shift $shift, $soldiers, $taskWeight)
    {
        $relevantsSoldier = $this->getRelevantSoldiers($shift, $soldiers);
        $soldiersAvailability = $this->calcSoldiersAvailability($relevantsSoldier, $shift->range);
        $this->shiftsData->push(new ShiftData(
            $shift,
            $this->getShiftWeight(
                $taskWeight,
                $shift,
                $soldiers->count(),
                $soldiersAvailability
            )
        ));
    }

    protected function getRelevantSoldiers(Shift $shift, $soldiers)
    {
        return $soldiers->filter(function (Soldier $soldier) use ($shift): bool {
            if ($shift->points > $soldier->pointsMaxData->remaining()) {
                return false;
            }

            return match ($shift->kind) {
                TaskKind::NIGHT->value => $soldier->nightsMaxData->remaining() > 0 && $soldier->shiftsMaxData->remaining() > 0,
                TaskKind::ALERT->value => $soldier->alertsMaxData->remaining() > 0,
                TaskKind::WEEKEND->value => $soldier->weekendsMaxData->remaining() >= $shift->points,
                TaskKind::REGULAR->value => $soldier->shiftsMaxData->remaining() > 0,
            };
        });
    }

    protected function getRatio($required, $available): float
    {
        return $available == 0 ? 0 : (float) $this->maximumOne((float) $required / $available);
    }

    protected function maximumOne(float $number): float
    {
        return $number > 1 ? 1 : $number;
    }

    protected function calcSoldiersAvailability($soldiers, Range $range)
    {
        $sumAvailability = 0;
        $soldiers
            ->each(function (Soldier $soldier) use ($range, &$sumAvailability) {
                $availability = $soldier->isAvailableByConstraints($range);
                match ($availability) {
                    Availability::NO => null,
                    Availability::BETTER_NOT => [
                        $this->soldiersDict[$soldier->id] = $this->soldiersDict[$soldier->id] + 4,
                        $sumAvailability += 0.8,
                    ],
                    Availability::YES => [
                        $this->soldiersDict[$soldier->id] = $this->soldiersDict[$soldier->id] + 5,
                        $sumAvailability++,
                    ],
                };
            });

        return $sumAvailability;
    }

    protected function getShiftWeight($taskWeight, Shift $shift, $soldiersCount, $soldiersAvailability)
    {
        $weight = $shift->points > 0 ? $this->maximumOne($taskWeight['pointsRatio'] * $shift->points) : 0;

        match ($shift->kind) {
            TaskKind::WEEKEND->value => $weight += $taskWeight['weekendsRatio'],
            TaskKind::NIGHT->value => $weight += $taskWeight['nightsRatio'] + $taskWeight['shiftsRatio'],
            TaskKind::ALERT->value => $weight += $taskWeight['alertsRatio'],
            TaskKind::REGULAR->value => $weight += $taskWeight['shiftsRatio'],
            default => null
        };
        $weight += $shift->points >= 2 && ! $shift->isAttached ? $this->shiftDumbbells['isFullWeekend'] : 0;
        $weight += $shift->isAttached ? 0 : $this->shiftDumbbells['isSingleShift'];
        $weight += $this->getShiftAvailabilityRatio($soldiersCount, $soldiersAvailability)
            * $this->shiftDumbbells['shiftAvailability'];

        $weight += $this->getShiftPointsRatio($shift->points) * $this->shiftDumbbells['shiftPointRatio'];

        return $weight;
    }

    protected function getShiftAvailabilityRatio($soldiersCount, $soldiersAvailability)
    {
        if ($soldiersAvailability == 0) {
            return 0;
        }

        return (float) ($soldiersCount - $soldiersAvailability) / $soldiersCount;
    }

    protected function getShiftPointsRatio($points)
    {
        if ($points == 0) {
            return 0;
        }

        return (float) $points / 3;
    }

    protected function getSortedShiftsList()
    {
        $this->shiftsData = $this->shiftsData->groupBy(fn ($shiftData) => (string) $shiftData->shift->points);

        return $this->shiftsData->map(
            fn ($shiftData) => collect($shiftData)
                ->sortByDesc(fn ($shift) => $shift->weight)
                ->map(fn ($shift) => $shift->shift)
                ->values()
                ->all()
        );
    }

    protected function initCourses()
    {
        $this->soldiers
            ->groupBy('course')
            ->map(function ($soldiers, $course) {
                return $soldiers
                    ->groupBy(fn ($soldier) => $soldier->pointsMaxData->max)
                    ->each(function ($courseSoldiers, $capacity) use ($course) {
                        $this->courses->push($this->buildCourse($course, $capacity, $courseSoldiers));
                    });
            });
        $this->courses = $this->courses->sortByDesc(fn (Course $course) => [$course->max, $course->number]);

    }

    protected function buildCourse($number, $capacity, $soldiers)
    {
        $sortedSoldiers = collect([]);
        collect($soldiers)
            ->each(
                function (Soldier $soldier) use (&$sortedSoldiers) {
                    $sortedSoldiers->push(['soldier' => $soldier, 'weight' => $this->soldiersDict[$soldier->id]]);
                }
            );
        $sortedSoldiers = $sortedSoldiers
            ->sortBy(fn ($soldierData) => $soldierData['weight'])
            ->map(fn ($soldier) => $soldier['soldier'])
            ->values()
            ->all();

        return new Course($number, $capacity, $sortedSoldiers);
    }

    protected function assign()
    {
        $this->courses
            ->filter(fn (Course $course) => $course->max > 0)
            ->each(function (Course $course) {
                if (collect($this->shiftsData)->contains(fn ($shifts): bool => collect($shifts)->count() > 0) && $course->max >= 2) {
                    $this->assignShiftsForCourse($course, true);
                }
            })
            ->each(function (Course $course) {
                if (collect($this->shiftsData)->contains(fn ($shifts): bool => collect($shifts)->count() > 0)) {
                    $this->assignShiftsForCourse($course, false);
                }
            });
    }

    protected function assignShiftsForCourse(Course $course, $isSingleRotation)
    {
        $rotationSize = $this->maximalRotationSize;
        while (! $course->hasGap && $course->remaining('pointsMaxData') > 0 && $rotationSize >= $this->minimalRotationSize) {
            $rotationResult = $this->rotation($course, $rotationSize);
            if ($rotationResult) {
                $this->saveRotationAssigments();
            } else {
                $this->clearRotationAsssigments();
                $rotationSize /= 2;
            }
            if ($isSingleRotation) {
                return;
            }
        }
    }

    protected function rotation($course, $rotationSize)
    {
        foreach ($course->soldiers as $soldier) {
            $assignedPoints = $this->assignRotationShiftsToSoldier($soldier, $rotationSize);

            $gap = $rotationSize - $assignedPoints;
            if ($gap > $this->allowedGap) {
                return false;
            }
            if ($gap > 0) {
                $course->hasGap = true;
            }
        }

        return true;
    }

    protected function assignRotationShiftsToSoldier(Soldier $soldier, float $rotationSize)
    {
        $points = $this->assignRotation($soldier, $rotationSize, false);
        $remaining = $rotationSize - $points;
        if ($remaining <= $this->allowedGap) {
            return $points;
        }
        $points += $this->assignRotation($soldier, $remaining, true);
        if ($rotationSize - $points <= $this->allowedGap) {
            return $points;
        }

        return 0;
    }

    protected function assignRotation(Soldier $soldier, float $rotationSize, bool $ignoreLowConstraint)
    {
        $currentRotationSize = $rotationSize;
        $shiftsCount = 1;
        $points = 0;
        $potentialShifts = collect([]);
        while ($points < $rotationSize && $currentRotationSize >= $this->minimalRotationSize) {
            $potentialShifts->push(...$this->getPotentialShifts($soldier, $currentRotationSize, $shiftsCount, $ignoreLowConstraint));
            $points = $this->sumPoints($potentialShifts);
            $currentRotationSize /= 2;
            $shiftsCount = ($rotationSize - $points) / $currentRotationSize;
        }

        return $points;
    }

    protected function getPotentialShifts(Soldier $soldier, $rotationSize, $shiftsCount, bool $ignoreLowConstraint)
    {
        if ($shiftsCount <= 0 || ! isset($this->shiftsData[(string) $rotationSize])) {
            return [];
        }
        $potentialShifts = collect([]);
        $counter = 0;
        foreach ($this->shiftsData[(string) $rotationSize] as $shift) {
            if ((! $shift->isAssigned()) && $soldier->isAbleTake($shift, $ignoreLowConstraint)) {
                $potentialShifts->push($shift);
                $this->addToRotationAssigments($soldier, $shift);

                $counter++;
                if ($counter == $shiftsCount) {
                    return $potentialShifts;
                }
            }
        }

        return $potentialShifts;
    }

    protected function sumPoints($shifts)
    {
        return collect($shifts)->sum(fn ($shift): float => $shift->points);
    }

    protected function addToRotationAssigments(Soldier $soldier, Shift $shift)
    {
        $shift->isAssigned = true;
        if ($shift instanceof AttachedWeekends) {
            $shift->fridayShift->isAssigned = true;
            $shift->saturdayShift->isAssigned = true;
            $this->rotationAssignments->push(new Assignment($shift->fridayShift->id, $soldier->id));
            $this->rotationAssignments->push(new Assignment($shift->saturdayShift->id, $soldier->id));
            $soldier->assign($shift->fridayShift, $shift->fridayShift->getShiftSpaces($soldier->shifts));
            $soldier->assign($shift->saturdayShift, $shift->saturdayShift->getShiftSpaces($soldier->shifts));

        } else {
            $this->rotationAssignments->push(new Assignment($shift->id, $soldier->id));
            $soldier->assign($shift, $shift->getShiftSpaces($soldier->shifts));
        }
    }

    protected function saveRotationAssigments()
    {
        $this->assignments->push(...$this->rotationAssignments);

        $this->rotationAssignments = collect([]);
        $this->shiftsData = $this->shiftsData->map(fn ($shiftData) => collect($shiftData)->filter(function ($shift) {
            return ! $shift->isAssigned();
        }));
    }

    protected function clearRotationAsssigments()
    {
        $this->shiftsData->map(fn ($shiftData) => collect($shiftData)->map(fn ($shift) => $shift->isAssigned = false));
        $this->rotationAssignments->map(function (Assignment $assignment) {
            $shift = $this->shiftsData->flatten()->firstWhere('id', $assignment->shiftId);
            $course = $this->courses->first(function ($course) use ($assignment) {
                return $course->soldiers->contains('id', $assignment->soldierId);
            });

            $soldier = $course ? $course->soldiers->firstWhere('id', $assignment->soldierId) : null;
            $soldier->unassign($shift, $shift->getShiftSpaces($soldier->shifts));
        });
        $this->rotationAssignments = collect([]);
    }
}
