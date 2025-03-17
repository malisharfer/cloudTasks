<?php

namespace App\Services;

use App\Enums\DaysInWeek;
use App\Enums\TaskKind;

class Shift
{
    public $id;

    public $taskType;

    public $range;

    public $points;

    public $kind;

    public $inParalelTasks;

    public function __construct($id, string $taskType, $start, $end, float $points, $kind, $inParalelTasks = [])
    {
        $this->id = $id;
        $this->taskType = $taskType;
        $this->range = new Range($start, $end);
        $this->points = $points;
        $this->kind = $kind;
        $this->inParalelTasks = $inParalelTasks;
    }

    public function getShiftSpaces($shifts)
    {
        return match ($this->kind) {
            TaskKind::WEEKEND->value => $this->getWeekendSpaces($shifts),
            TaskKind::NIGHT->value => $this->range->getNightSpaces(),
            default => []
        };
    }

    protected function getWeekendSpaces($shifts)
    {
        return $this->isFullWeekend($shifts) ? [$this->range->getDayAfterWeekend()] : null;
    }

    protected function isFullWeekend($shifts)
    {
        $isFriday = $this->isShiftInclude($this->range, DaysInWeek::FRIDAY);
        $isSaturday = $this->isShiftInclude($this->range, DaysInWeek::SATURDAY);
        if ($isFriday && $isSaturday) {
            return true;
        }
        $dayToCheck = $isFriday ? DaysInWeek::SATURDAY : DaysInWeek::FRIDAY;
        if (! empty($shifts)) {
            $shiftsInWeekend = $shifts->filter(function ($shift) use ($dayToCheck): bool {
                return $this->isShiftInclude($shift->range, $dayToCheck);
            });
        }

        return ! empty($shiftsInWeekend) ? $this->isAttached($shiftsInWeekend, $this->range, $dayToCheck) : false;
    }

    protected function isShiftInclude(Range $range, DaysInWeek $dayInWeek): bool
    {
        return $range->isRangeInclude($dayInWeek);
    }

    protected function isAttached($shifts, $range, DaysInWeek $dayInWeek): bool
    {
        $expectedDate = $dayInWeek == DaysInWeek::FRIDAY ? $range->start->subDay()->startOfDay() : $range->end->addDay()->startOfDay();

        return $shifts ? collect($shifts)->contains(
            function ($shift) use ($expectedDate): bool {
                return $shift->range->start->isSameDay($expectedDate);
            }
        ) : false;
    }
}
