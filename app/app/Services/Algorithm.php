<?php

namespace App\Services;

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use App\Services\Shift as ShiftService;
use Carbon\Carbon;

class Algorithm
{
    protected $date;

    public function __construct($date = null)
    {
        $this->date = $date ? Carbon::parse($date) : now()->addMonth();
    }

    protected function getShiftWithTasks()
    {
        // $startOfMonth = max($this->date->copy()->startOfMonth(), Carbon::tomorrow());
        $startOfMonth = $this->date->copy()->startOfMonth();
        $endOfMonth = $this->date->copy()->endOfMonth();

        return Shift::query()
            ->with(['task' => fn ($q) => $q->withTrashed()])
            ->whereNull('soldier_id')
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->whereHas('task', function ($query) {
                $query->withTrashed()
                    ->where('kind', '!=', TaskKind::INPARALLEL->value);
            })
            ->lazy()
            ->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));
    }

    protected function getSoldiersDetails()
    {
        $range = new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth());

        return Soldier::where('is_reservist', false)
            ->with([
                'constraints' => fn ($q) => $q->whereBetween('start_date', [$range->start, $range->end]),
                'shifts' => fn ($q) => $q->whereBetween('start_date', [$range->start, $range->end])
                    ->whereHas('task', function ($query) {
                        $query->withTrashed()->where('kind', '!=', TaskKind::INPARALLEL->value);
                    }),
            ])
            ->lazy()
            ->map(function (Soldier $soldier) {
                $constraints = Helpers::buildConstraints($soldier->constraints);

                $shifts = $soldier->shifts->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));

                $shifts->push(...Helpers::addShiftsSpaces($shifts));
                $shifts->push(...Helpers::addPrevMonthSpaces($soldier->id, $this->date));

                $capacityHold = Helpers::capacityHold($shifts);

                return Helpers::buildSoldier($soldier, $constraints, $shifts, $capacityHold);
            })
            ->filter(fn ($soldier) => $soldier->hasMaxes())
            ->shuffle();
    }

    protected function getSoldiersShifts($soldierId, $inParallel)
    {
        return Helpers::getSoldiersShifts($soldierId, new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()), $inParallel);
    }

    public function run()
    {
        $shifts = $this->getShiftWithTasks();
        $soldiers = $this->getSoldiersDetails();
        $scheduleAlgorithm = new Schedule($shifts, $soldiers);
        $scheduleAlgorithm->schedule();
        $concurrentTasks = new ConcurrentTasks($this->date);
        $concurrentTasks->run();
    }
}
