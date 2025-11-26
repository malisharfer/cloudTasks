<?php

namespace App\Services;

use App\Enums\TaskKind;

class Schedule
{
    public $shifts;

    public $soldiers;

    public $assignments;

    public function __construct($shifts, $soldiers)
    {
        $this->soldiers = collect($soldiers);
        $this->shifts = collect($shifts);
        $this->assignments = collect([]);
    }

    public function schedule()
    {
        $this->assignPointedShifts();
        $this->assignNotPointedShifts();
        $this->saveAssignments();
    }

    protected function assignPointedShifts()
    {
        $pointedShifts = $this->shifts->filter(fn (Shift $shift): bool => $shift->points > 0);
        $soldiers = $this->soldiers->filter(fn (Soldier $soldier) => $soldier->pointsMaxData->remaining() > 0);
        $pointedSchedule = new PointedSchedule($soldiers, $pointedShifts);
        $assignments = $pointedSchedule->schedule();
        $this->assignments->push(...$assignments);
    }

    protected function assignNotPointedShifts()
    {
        $taskKinds = collect([
            TaskKind::NIGHT->value => 1,
            TaskKind::REGULAR->value => 3,
            TaskKind::ALERT->value => 2,
        ]);
        $taskKinds->each(function ($gap, $taskKind) {
            $shifts = $this->shifts->filter(fn (Shift $shift): bool => $shift->kind == $taskKind &&
                $shift->points == 0);
            $notPointedSchedule = new NotPointedSchedule($taskKind, $this->soldiers, $shifts, $gap);
            $assignments = $notPointedSchedule->schedule();
            $this->assignments->push(...$assignments);
        });
    }

    protected function saveAssignments()
    {
        Helpers::updateShiftTable($this->assignments);
    }
}