<?php

namespace App\Services;

use App\Enums\Availability;
use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;
use Carbon\Carbon;

class ConcurrentTasks
{
    protected $date;

    protected $shifts;

    protected $soldiers;

    protected $shiftsData;

    protected $assignments;

    public function __construct($date = null)
    {
        $this->date = $date ? Carbon::parse($date) : now()->addMonth();
        $this->shiftsData = collect([]);
        $this->assignments = collect([]);
    }

    public function run()
    {
        $this->shifts = $this->getShiftsWithTasks();
        $this->soldiers = $this->getSoldiersDetails();
        $this->initShiftsData();
        $this->assignShifts();
        Helpers::updateShiftTable($this->assignments);
    }

    protected function getShiftsWithTasks()
    {
        return Shift::whereNull('soldier_id')
            ->get()
            ->filter(function (Shift $shift) {
                $range = new Range($shift->start_date, $shift->end_date);

                return $range->isSameMonth(new Range(max($this->date->copy()->startOfMonth(), Carbon::tomorrow()), $this->date->copy()->endOfMonth()))
                    && $shift->task->in_parallel;
            })
            ->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));
    }

    protected function getSoldiersDetails()
    {
        return Soldier::with('constraints')
            ->where('is_reservist', false)
            ->get()
            ->map(function (Soldier $soldier) {
                $constraints = Helpers::buildConstraints($soldier->constraints, new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()));
                $shifts = $this->getSoldiersShifts($soldier->id, false);
                $concurrentsShifts = $this->getSoldiersShifts($soldier->id, true);
                $shifts->push(...Helpers::addShiftsSpaces($shifts));
                $capacityHold = Helpers::capacityHold($shifts);

                return Helpers::buildSoldier($soldier, $constraints, $shifts, $capacityHold, $concurrentsShifts);
            })
            ->shuffle();
    }

    protected function getSoldiersShifts($soldierId, $inParallel)
    {
        return Helpers::getSoldiersShifts($soldierId, new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()), $inParallel);
    }

    protected function initShiftsData(): void
    {
        $groupedShifts = collect($this->shifts)->groupBy('taskType');
        $groupedShifts->each(function ($shifts, $taskType) {
            $this->addShiftsDataByTask($taskType, collect($shifts));
        });
    }

    protected function addShiftsDataByTask(string $taskType, $shifts): void
    {
        $soldiers = collect($this->soldiers)->filter(function (SoldierService $soldier) use ($taskType): bool {
            return $soldier->isQualified($taskType);
        });
        $shifts->map(fn ($shift) => $this->addShiftData($shift, $soldiers));
    }

    protected function addShiftData(ShiftService $shift, $soldiers)
    {
        $potentialSoldiers = $this->getPotentialSoldiers($soldiers, $shift);
        $shiftData = new ShiftData(
            $shift,
            $potentialSoldiers,
            0
        );
        $this->shiftsData->push($shiftData);
    }

    protected function getPotentialSoldiers($soldiers, ShiftService $shift)
    {
        $potentialSoldiers = $soldiers
            ->filter(function (SoldierService $soldier) use ($shift) {
                return $soldier->isAvailableByConstraints($shift->range) === Availability::YES
                    && $soldier->isAvailableByConcurrentsShifts($shift)
                    && $soldier->inParallelMaxData->remaining() > 0
                    && $this->isAvailableByShiftsAndSpaces($soldier->shifts, $shift);
            });

        return $potentialSoldiers;
    }

    protected function isAvailableByShiftsAndSpaces($soldierShifts, ShiftService $shift): bool
    {
        return ! $soldierShifts->contains(function (ShiftService $soldierShift) use ($shift): bool {
            return $soldierShift->range->isConflict($shift->range) && ! collect($shift->inParalelTasks)->contains($soldierShift->taskType);
        });
    }

    protected function assignShifts()
    {
        collect($this->shiftsData)->map(function (ShiftData $shiftData) {
            $this->assignShift($shiftData);
        });
    }

    protected function assignShift(ShiftData $shiftData)
    {
        foreach ($shiftData->potentialSoldiers as $potentialSoldier) {
            $success = $this->tryAssignShift($potentialSoldier, $shiftData->shift);
            if ($success) {
                return;
            }
        }
    }

    protected function tryAssignShift(SoldierService $soldier, ShiftService $shift)
    {
        if ($soldier->isAvailableByConcurrentsShifts($shift)) {
            $soldier->concurrentsShifts->push($shift);
            $this->assignments->push(new Assignment($shift->id, $soldier->id));
            $soldier->inParallelMaxData->used++;

            return true;
        }

        return false;
    }
}
