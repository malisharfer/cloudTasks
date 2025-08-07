<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\RotationResult;

class NotPointedSchedule
{
    protected $taskKind;

    protected $soldiers;

    protected $shifts;

    protected $allowedGap;

    protected $courses;

    protected $shiftsData;

    protected $assignments;

    protected $shiftDumbbells;

    protected $availabilityDumbbells;

    protected $maxName;

    protected $tasksData;

    public function __construct($taskKind, $soldiers, $shifts, $allowedGap)
    {
        $this->taskKind = $taskKind;
        $this->soldiers = collect($soldiers);
        $this->shifts = collect($shifts);
        $this->allowedGap = $allowedGap;
        $this->shiftsData = collect([]);
        $this->assignments = collect([]);
        $this->shiftDumbbells = collect([
            'KIND_RELATIVE_LOAD' => 0.5,
            'SHIFT_AVAILABILITY' => 0.5,
        ]);
        $this->availabilityDumbbells = collect([
            Availability::NO->value => 0,
            Availability::BETTER_NOT->value => 0.7,
            Availability::YES->value => 1,
        ]);
        $this->maxName = $this->getMaxName();
        $this->courses = collect([]);
        $this->tasksData = collect([]);
    }

    protected function getMaxName(): string
    {
        return match ($this->taskKind) {
            'Night' => 'nightsMaxData',
            'Regular' => 'shiftsMaxData',
            'Alert' => 'alertsMaxData',
        };
    }

    public function schedule()
    {
        $this->buildShiftsDict();
        $this->initCourses();
        $this->assign();

        return $this->assignments;
    }

    protected function buildShiftsDict()
    {
        $groupedShifts = collect($this->shifts)->groupBy('taskType');
        $groupedShifts->each(function ($shifts, $taskType) {
            $this->tasksData[$taskType] = $shifts->count();
            $this->addShiftsDataByTask($taskType, collect($shifts));
        });
        $this->shiftsData = $this->sortShiftsDict();
    }

    protected function addShiftsDataByTask($taskType, $shifts): void
    {
        $soldiers = $this->soldiers->filter(fn (Soldier $soldier) => $soldier->isQualified($taskType));
        $taskWeight = $this->getTaskWeight($shifts, $soldiers);
        $shifts->map(fn ($shift) => $this->addShiftData($shift, $soldiers, $taskWeight));
    }

    protected function getTaskWeight($shifts, $soldiers)
    {
        $available = collect($soldiers)->sum(fn (Soldier $soldier) => $soldier->{$this->maxName}->remaining());

        return $this->getRatio($shifts->count(), $available) * $this->shiftDumbbells['KIND_RELATIVE_LOAD'];
    }

    protected function getRatio(float $required, float $available): float
    {
        return $available == 0 ? 0 : (float) $this->maximumOne((float) $required / $available);
    }

    protected function maximumOne(float $number): float
    {
        return $number > 1 ? 1 : $number;
    }

    protected function addShiftData(Shift $shift, $soldiers, $taskWeight)
    {
        $soldiersAvailability = $this->getSoldiersAvailability($soldiers, $shift->range);
        $this->shiftsData->push(new ShiftData(
            $shift,
            $this->getShiftWeight(
                $taskWeight,
                $soldiers->count(),
                $soldiersAvailability
            )
        ));
    }

    protected function getSoldiersAvailability($soldiers, $range)
    {
        return collect($soldiers)
            ->sum(
                function (Soldier $soldier) use ($range) {
                    $availability = $soldier->isAvailableByConstraints($range);

                    return $this->availabilityDumbbells[$availability->value];
                }
            );
    }

    protected function getShiftWeight($taskWeight, $soldiersCount, $soldiersAvailability): float
    {
        return $taskWeight +
            $this->getShiftAvailabilityRatio($soldiersCount, $soldiersAvailability)
            * $this->shiftDumbbells['SHIFT_AVAILABILITY'];
    }

    protected function getShiftAvailabilityRatio($soldiersCount, $soldiersAvailability): float
    {
        if ($soldiersAvailability == 0) {
            return 0;
        }

        return (float) ($soldiersCount - $soldiersAvailability) / $soldiersCount;
    }

    protected function sortShiftsDict()
    {
        return $this->shiftsData
            ->sortByDesc(fn ($shift) => $shift->weight)
            ->map(fn ($shift) => $shift->shift)
            ->values()
            ->all();
    }

    protected function initCourses()
    {
        collect($this->soldiers)
            ->filter(fn (Soldier $soldier): bool => $soldier->{$this->maxName}->remaining() > 0)
            ->groupBy('course')
            ->map(fn ($soldiers, $course) => $soldiers
                ->groupBy(fn (Soldier $soldier) => $soldier->{$this->maxName}->max)
                ->each(function ($courseSoldiers, $capacity) use ($course) {
                    $this->courses->push($this->buildCourse($course, $capacity, $courseSoldiers));
                }));

        $this->courses = $this->courses->sortByDesc(fn (Course $course) => [$course->max, $course->number]);
    }

    protected function buildCourse($number, $capacity, $soldiers)
    {
        $sortedSoldiers = collect($soldiers)
            ->map(fn (Soldier $soldier) => ['soldier' => $soldier, 'weight' => $this->getSoldierWeight($soldier)])
            ->sortBy(fn ($soldierData) => $soldierData['weight'])
            ->pluck('soldier')
            ->values()
            ->all();

        return new Course($number, $capacity, $sortedSoldiers);
    }

    protected function getSoldierWeight(Soldier $soldier)
    {
        return collect($soldier->qualifications)->sum(fn ($qualification) => $this->tasksData[$qualification] ?? 0);
    }

    protected function assign()
    {
        $this->courses->each(function (Course $course) {
            $this->assignShiftsForCourse($course);
        });
    }

    protected function assignShiftsForCourse(Course $course)
    {
        $courseGap = 0;
        while ($course->remaining($this->maxName) > 0 && $courseGap < $this->allowedGap && $this->isUnassignedShiftsExist()) {
            $rotationResult = $this->rotation($course);
            if ($rotationResult == RotationResult::SUCCESS_WITH_GAP) {
                $courseGap += 1;                
            }
            $course->used += 1;
        }
    }

    protected function isUnassignedShiftsExist(): bool
    {
        return collect($this->shiftsData)->contains(fn (Shift $shift): bool => ! $shift->isAssigned);
    }

    protected function rotation(Course $course)
    {
        $isGapExists = false;
        $soldiers = $course->soldiers->filter(fn(Soldier $soldier)=>$soldier->{$this->maxName}->used <= $course->used);
        collect($soldiers)->each(function (Soldier $soldier) use (&$isGapExists) {
            $success = $this->assignRotationShiftToSoldier($soldier);
            if (! $success) {
                $isGapExists = true;
            }
        });

        return $isGapExists ? RotationResult::SUCCESS_WITH_GAP : RotationResult::SUCCESS;
    }

    protected function assignRotationShiftToSoldier(Soldier $soldier)
    {
        if ($this->tryAssign($soldier, false)) {
            return true;
        }

        return $this->tryAssign($soldier, true);
    }

    protected function tryAssign(Soldier $soldier, bool $ignoreLowConsraint)
    {
        foreach ($this->shiftsData as $shift) {
            if (! $shift->isAssigned && $soldier->isAbleTake($shift, $ignoreLowConsraint)) {
                $soldier->assign($shift, $shift->getShiftSpaces($soldier->shifts));
                $shift->isAssigned = true;
                $this->assignments->push(new Assignment($shift->id, $soldier->id));

                return true;
            }
        }

        return false;
    }
}