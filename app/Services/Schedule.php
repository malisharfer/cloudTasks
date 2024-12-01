<?php

namespace App\Services;

use App\Enums\Availability;

class Schedule
{
    public $shifts;

    public $soldiers;

    public $shiftsData;

    public $soldiersDict;

    public $assignments;

    public $unAssignments;

    public $SHIFT_DUMBBELLS;

    public $SOLDIER_DUMBBELLS;

    public function __construct($shifts, $soldiers)
    {
        $this->shifts = collect($shifts);
        $this->soldiers = collect($soldiers);
        $this->shiftsData = collect([]);
        $this->soldiersDict = collect([]);
        $this->assignments = collect([]);
        $this->unAssignments = collect([]);
        $this->SHIFT_DUMBBELLS = collect([
            'POINTS_RATIO' => 0.29,
            'SHIFTS_RATIO' => 0.39,
            'WEEKENDS_RATIO' => 0.04,
            'NIGHTS_RATIO' => 0.04,
            'SHIFT_AVAILABILITY' => 0.23,
            'BLOCK_POINTS' => 0.05,
        ]);
        $this->SOLDIER_DUMBBELLS = collect([
            'LOW_CONSTRAINT' => 0.45,
            'POINTS_RELATIVE_LOAD' => 0.4,
            'SHIFTS_RELATIVE_LOAD' => 0.08,
            'NIGHT_RELATIVE_LOAD' => 0.01,
            'WEEKEND_RELATIVE_LOAD' => 0.01,
            'MULTITASKING_VALUE' => 0.05,
        ]);
    }

    public function schedule(): void
    {
        $this->initShiftsData();
        $this->initSoldiersData();
        $sortedShifts = $this->getSortedShiftsList();
        collect($sortedShifts)->map(function (ShiftData $shift) {
            $success = $this->assignShift($shift);
            if (! $success) {
                $this->unAssignments->push($shift->shift);
            }
        });
        $this->updateDB();
    }

    protected function initShiftsData(): void
    {
        $groupedShifts = collect($this->shifts)->groupBy('taskType');
        $groupedShifts->each(callback: function ($shifts, $taskType) {
            $this->addShiftsDataByTask($taskType, collect($shifts));
        });
    }

    protected function addShiftsDataByTask(string $taskType, $shifts): void
    {
        $soldiers = $this->soldiers->filter(function (Soldier $soldier) use ($taskType): bool {
            return $soldier->isQualified($taskType);
        });
        $taskWeight = $this->getTaskWeight($shifts, $soldiers);
        $shifts->map(fn ($shift) => $this->addShiftData($shift, $soldiers, $taskWeight));
    }

    protected function getTaskWeight($shifts, $soldiers): array
    {
        $requiredPoints = collect($shifts)->sum('points');
        $requiredNights = collect($shifts)->sum(function (Shift $shift) {
            return $shift->isNight;
        });
        $requiredWeekends = collect($shifts)->sum(function (Shift $shift) {
            return $shift->isWeekend;
        });
        $requiredShifts = count($shifts);

        $availablePoints = collect($soldiers)->sum(function (Soldier $soldier) {
            return $soldier->pointsMaxData->max;
        });

        $availableNights = collect($soldiers)->sum(function ($soldier) {
            return $soldier->nightsMaxData->max;
        });

        $availableWeekends = collect($soldiers)->sum(function ($soldier) {
            return $soldier->weekendsMaxData->max;
        });

        $availableShifts = collect($soldiers)->sum(function ($soldier) {
            return $soldier->shiftsMaxData->max;
        });

        $weight = collect([
            'POINTS_RATIO' => $this->getRatio($requiredPoints, $availablePoints),
            'NIGHTS_RATIO' => $this->getRatio($requiredNights, $availableNights),
            'WEEKENDS_RATIO' => $this->getRatio($requiredWeekends, $availableWeekends),
            'SHIFTS_RATIO' => $this->getRatio($requiredShifts, $availableShifts),
        ]);

        $weight->each(function ($value, $key) use (&$weight) {
            $weight[$key] = $value * $this->SHIFT_DUMBBELLS[$key];
        });

        return $weight->all();
    }

    protected function addShiftData(Shift $shift, $soldiers, $taskWeight)
    {
        $potentialSoldiers = $this->getPotentialSoldiers($soldiers, $shift->range);
        $shiftData = new ShiftData(
            $shift,
            $potentialSoldiers,
            $this->getShiftWeight(
                $taskWeight,
                $shift,
                $soldiers->count(),
                $potentialSoldiers->count()
            )
        );
        $this->shiftsData->push($shiftData);
    }

    protected function getPotentialSoldiers($soldiers, Range $range)
    {
        $potentialSoldiers = $soldiers->filter(function (Soldier $soldier) use ($range) {
            return $soldier->isAvailableByConstraints($range) != Availability::NO;
        })->map(function (Soldier $soldier) use ($range) {
            $availability = $soldier->isAvailableByConstraints($range);

            return new PotentialSoldierData(
                $soldier->id,
                $availability == Availability::BETTER_NOT
            );
        });

        return $potentialSoldiers;
    }

    protected function getShiftWeight($taskWeight, Shift $shift, int $soldiersCount, int $availableSoldiersCount): float
    {
        $weight = $taskWeight['SHIFTS_RATIO'] + $shift->points > 0 ? $taskWeight['POINTS_RATIO'] : 0;

        if ($shift->isWeekend) {
            $weight += $taskWeight['WEEKENDS_RATIO'];
        } elseif ($shift->isNight) {
            $weight += $taskWeight['NIGHTS_RATIO'];
        }
        $weight += $this->getShiftAvailabilityRatio($soldiersCount, $availableSoldiersCount)
            * $this->SHIFT_DUMBBELLS['SHIFT_AVAILABILITY'];

        $weight += $this->getShiftBlockPoints($shift->points) * $this->SHIFT_DUMBBELLS['BLOCK_POINTS'];

        return $weight;
    }

    protected function getShiftBlockPoints(float $points): float
    {
        if ($points == 0) {
            return 0;
        }

        return (float) $points / 3;
    }

    protected function getShiftAvailabilityRatio(int $soldiersCount, int $availableSoldiers): float
    {
        if ($availableSoldiers == 0) {
            return 0;
        }

        return (float) ($soldiersCount - $availableSoldiers) / $soldiersCount;
    }

    protected function initSoldiersData(): void
    {
        $this->soldiers->map(fn (Soldier $soldier) => $this->soldiersDict->put($soldier->id, $soldier));
    }

    protected function getSortedShiftsList()
    {
        return $this->shiftsData->sortByDesc('weight');
    }

    protected function assignShift(ShiftData $shiftData): bool
    {
        $soldiers = $this->getPotentialSoldiersData($shiftData);
        foreach ($soldiers as $soldier) {
            $success = $this->tryAssign($soldier, $shiftData->shift);
            if ($success) {
                return true;
            }
        }

        return false;
    }

    protected function getPotentialSoldiersData(ShiftData $shiftData)
    {
        $soldiers = collect([]);
        collect($shiftData->potentialSoldiers)->map(function (PotentialSoldierData $potentialSoldierData) use (&$soldiers, $shiftData) {
            $soldiers->push($this->getSoldierAndWeight($potentialSoldierData, $shiftData->shift));
        });

        return $this->getSortedPotentialSoldiers($soldiers);
    }

    protected function getSoldierAndWeight(PotentialSoldierData $potentialSoldierData, Shift $shift)
    {
        $soldier = $this->soldiersDict[$potentialSoldierData->soldierId];
        $weightDict = [
            'LOW_CONSTRAINT' => $potentialSoldierData->isLowConstraint ? 1 : 0,
            'POINTS_RELATIVE_LOAD' => $shift->points > 0 ? $soldier->pointsMaxData->calculatedRelativeLoad() : 0,
            'SHIFTS_RELATIVE_LOAD' => $soldier->shiftsMaxData->calculatedRelativeLoad(),
            'NIGHT_RELATIVE_LOAD' => ! $shift->isNight ? 0 : $soldier->nightsMaxData->calculatedRelativeLoad(),
            'WEEKEND_RELATIVE_LOAD' => ! $shift->isWeekend ? 0 : $soldier->weekendsMaxData->calculatedRelativeLoad(),
            'MULTITASKING_VALUE' => $this->getMultitaskingValue(
                $soldier->qualifications->count()
            ),
        ];
        $weight = $this->getTotalWeight($weightDict);

        return [$soldier, $weight];
    }

    protected function getMultitaskingValue(int $qualificationsNumber): float
    {
        return (float) (1 - ((float) (1 / $qualificationsNumber)));
    }

    protected function getTotalWeight($weightData): float
    {
        $weight = 0;
        collect($weightData)->map(function ($value, $key) use (&$weight) {
            $weight += (float) ($value * $this->SOLDIER_DUMBBELLS[$key]);
        });

        return $weight;
    }

    protected function getSortedPotentialSoldiers($soldiers)
    {
        $sortedSoldiers = $soldiers->sortBy(function ($item) {
            return $item[1];
        });

        return $sortedSoldiers->map(fn ($soldier) => $soldier[0]);
    }

    protected function tryAssign(Soldier $soldier, Shift $shift): bool
    {
        $spaces = $shift->getShiftSpaces($soldier->shifts);
        if ($soldier->isAbleTake($shift, $spaces)) {
            $soldier->assign($shift, $spaces);
            $this->assignments->push(new Assignment($shift->id, $soldier->id));

            return true;
        }

        return false;
    }

    protected function getRatio(float $required, float $available): float
    {
        if ($available == 0) {
            return 0;
        }
        $ratio = (float) $required / $available;

        return (float) $this->maximumOne($ratio);
    }

    protected function maximumOne(float $number): float
    {
        if ($number > 1) {
            return 1;
        }

        return $number;
    }

    protected function updateDB()
    {
        collect($this->assignments)->map(fn (Assignment $assignment) => \App\Models\Shift::where('id', $assignment->shiftId)->update(['soldier_id' => $assignment->soldierId]));
    }
}