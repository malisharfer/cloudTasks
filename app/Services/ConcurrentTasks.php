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
        $endOfMonth = $this->date->copy()->endOfMonth();

        $results = collect();

        Shift::whereNull('soldier_id')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('start_date', '<=', $endOfMonth)
                    ->where('start_date', '>=', $startOfMonth);
            })
            ->whereHas('task', function ($query) {
                $query->withTrashed()
                    ->where('kind', TaskKind::INPARALLEL->value);
            })
            ->chunk(500, function ($shifts) use (&$results) {
                $mapped = $shifts->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));
                $results = $results->merge($mapped);
            });

        return $results;
    }
    protected function getSoldiersDetails()
    {
        $range = new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth());

        $soldiersCollection = collect();

        Soldier::where('is_reservist', false)
            ->with([
                'constraints' => fn ($q) => $q->whereBetween('start_date', [$range->start, $range->end]),
                'shifts' => fn ($q) => $q->whereBetween('start_date', [$range->start, $range->end]),
            ])
            ->chunk(100, function ($soldiers) use (&$soldiersCollection) {
                $mapped = $soldiers->map(function (Soldier $soldier) {
                    $constraints = Helpers::buildConstraints($soldier->constraints);
                    $shifts = Helpers::mapSoldierShifts($soldier->shifts, false);
                    $concurrentsShifts = Helpers::mapSoldierShifts($soldier->shifts, true);

                    $shifts->push(...Helpers::addShiftsSpaces($shifts));
                    $shifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->date));

                    $capacityHold = Helpers::capacityHold($shifts, $concurrentsShifts);

                    return Helpers::buildSoldier(
                        $soldier,
                        $constraints,
                        $shifts,
                        $capacityHold,
                        $concurrentsShifts
                    );
                });

                $soldiersCollection->push(...$mapped);
            });

        return $soldiersCollection
            ->keyBy('id')
            ->shuffle();
    }

    protected function initShiftsData(): void
    {
        $groupedShifts = collect($this->shifts)->groupBy('taskType');
        $groupedShifts->each(function ($shifts, $taskType) {
            $soldiers = $this->soldiers->filter(fn (SoldierService $soldier) => $soldier->isQualified($taskType));
            $shifts->each(fn ($shift) => $this->addShiftData($shift, $soldiers));
        });
    }

    protected function addShiftData(ShiftService $shift, $soldiers)
    {
        $shiftData = new ShiftData(
            $shift,
            0,
            $this->getPotentialSoldiers($soldiers, $shift)
        );
        $this->shiftsData->push($shiftData);
    }

    protected function getPotentialSoldiers($soldiers, ShiftService $shift)
    {
        return $soldiers
            ->filter(function (SoldierService $soldier) use ($shift) {
                return $soldier->isAvailableByConstraints($shift->range) === Availability::YES
                    && $soldier->isAvailableByConcurrentsShifts($shift)
                    && $soldier->inParallelMaxData->remaining() > 0
                    && $soldier->isAvailableByShifts($shift);
            })->shuffle();
    }

    protected function assignShifts()
    {
        collect($this->shiftsData)->map(fn (ShiftData $shiftData) => $this->assignShift($shiftData));
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
