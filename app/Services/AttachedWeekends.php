<?php

namespace App\Services;

use App\Enums\TaskKind;

class AttachedWeekends extends Shift
{
    public $fridayShift;

    public $saturdayShift;

    public function __construct(Shift $fridayShift, Shift $saturdayShift)
    {
        $this->fridayShift = $fridayShift;
        $this->saturdayShift = $saturdayShift;
        $this->fridayShift->isAttached = true;
        $this->saturdayShift->isAttached = true;
        $this->range = new Range($fridayShift->range->start, $saturdayShift->range->end);
        $this->points = $fridayShift->points + $saturdayShift->points;
        $this->taskId = $saturdayShift->taskId;
        $this->taskType = $saturdayShift->taskType;
        $this->isAssigned = false;
        $this->isAttached = true;
        $this->kind = TaskKind::WEEKEND->value;
    }

    public function isAssigned(): bool
    {
        return $this->isAssigned ||
            $this->fridayShift->isAssigned ||
            $this->saturdayShift->isAssigned;
    }
}
