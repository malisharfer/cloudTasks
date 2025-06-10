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
        $startOfMonth = max($this->date->copy()->startOfMonth(), Carbon::tomorrow());
        $endOfMonth = $this->date->copy()->endOfMonth();

        return Shift::whereNull('soldier_id')
            ->whereHas('task', function ($query) {
                $query->withTrashed()
                    ->where('kind', '!=', TaskKind::INPARALLEL->value);
            })
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where(function ($subQuery) use ($startOfMonth, $endOfMonth) {
                    $subQuery->where('start_date', '<=', $endOfMonth)
                        ->where('end_date', '>=', $startOfMonth);
                });
            })
            ->get()
            ->map(fn (Shift $shift): ShiftService => Helpers::buildShift($shift));
    }

    protected function getSoldiersDetails()
    {
        return Soldier::with('constraints')
            ->where('is_reservist', false)
            // ->whereJsonLength('qualifications', '>', 0)
            ->get()
            ->map(function (Soldier $soldier) {
                $constraints = Helpers::buildConstraints($soldier->constraints, new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()));

                $shifts = $this->getSoldiersShifts($soldier->id, false);

                $shifts->push(...Helpers::addShiftsSpaces($shifts));

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