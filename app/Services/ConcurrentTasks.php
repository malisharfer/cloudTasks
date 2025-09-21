<?php

namespace App\Services;

use App\Enums\Availability;
use App\Enums\TaskKind;
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
        $startOfMonth = $this->date->copy()->startOfMonth();
        // $startOfMonth = max($this->date->copy()->startOfMonth(), Carbon::tomorrow());
        $endOfMonth = $this->date->copy()->endOfMonth();

        return Shift::whereNull('soldier_id')
            ->whereHas('task', function ($query) {
                $query->withTrashed()
                    ->where('kind', TaskKind::INPARALLEL->value);
            })
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('start_date', '<=', $endOfMonth)
                    ->where('start_date', '>=', $startOfMonth);
            })
            ->get()
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
                $shifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->date));
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
        $groupedShifts->each(fn ($shifts, $taskType) => $this->addShiftsDataByTask($taskType, collect($shifts)));
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
            0,
            $potentialSoldiers
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
                    && $soldier->isAvailableByShifts($shift);
            });

        return collect($potentialSoldiers)->shuffle();
    }

    protected function assignShifts()
    {
        collect($this->shiftsData)->map(fn (ShiftData $shiftData) => $this->assignShift($shiftData)
        );
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
        if ($soldier->isAvailableByConcurrentsShifts($shift) && $soldier->inParallelMaxData->remaining() > 0) {
            $soldier->concurrentsShifts->push($shift);
            $soldier->inParallelMaxData->used++;
            $this->assignments->push(new Assignment($shift->id, $soldier->id));

            return true;
        }

        return false;
    }
}
