<?php

namespace App\Services;

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
        return Shift::whereNull('soldier_id')
            ->get()
            ->filter(function (Shift $shift) {
                $range = new Range($shift->start_date, $shift->end_date);

                return $range->isSameMonth(new Range(max($this->date->copy()->startOfMonth(), Carbon::tomorrow()), $this->date->copy()->endOfMonth()));
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

                $shifts = $this->getSoldiersShifts($soldier->id);

                $shifts->push(...Helpers::addShiftsSpaces($shifts));

                $capacityHold = Helpers::capacityHold($shifts);

                return Helpers::buildSoldier($soldier, $constraints, $shifts, $capacityHold);
            })
            ->shuffle()
            ->toArray();
    }

    protected function getSoldiersShifts($soldierId)
    {
        return Helpers::getSoldiersShifts($soldierId, new Range($this->date->copy()->startOfMonth(), $this->date->copy()->endOfMonth()));
    }

    public function run()
    {
        $shifts = $this->getShiftWithTasks();
        $soldiers = $this->getSoldiersDetails();
        $scheduleAlgorithm = new Schedule($shifts, $soldiers);
        $scheduleAlgorithm->schedule();
    }
}
