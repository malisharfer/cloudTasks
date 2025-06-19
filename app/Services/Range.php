<?php

namespace App\Services;

use App\Enums\DaysInWeek;
use Carbon\Carbon;
use Exception;

class Range
{
    public $start;

    public $end;

    public function __construct($start, $end)
    {
        if (Carbon::parse($start)->isAfter(Carbon::parse($end))) {
            new Exception('Invalid range');
        }
        $this->start = Carbon::parse($start)->setTimezone('Asia/Jerusalem');
        $this->end = Carbon::parse($end)->setTimezone('Asia/Jerusalem');
    }

    public function isConflict(Range $other): bool
    {
        return $this->start->isBefore($other->end) && $other->start->isBefore($this->end);
    }

    public function isSameMonth(Range $other): bool
    {
        return $this->isConflict($other);
    }

    public function isPass(): bool
    {
        return Carbon::now()->greaterThan($this->start) || Carbon::now()->greaterThan($this->end);
    }

    public function isRangeInclude(DaysInWeek $dayInWeek): bool
    {
        $startDayIndex = $this->start->dayOfWeek;
        $endDayIndex = $this->end->dayOfWeek;
        $checkDayIndex = date('N', strtotime($dayInWeek->value));
        if ($startDayIndex <= $endDayIndex) {
            return $this->start->copy()->diffInDays($this->end->copy()) > 5 || ($checkDayIndex >= $startDayIndex && $checkDayIndex <= $endDayIndex);
        }

        return $checkDayIndex >= $startDayIndex || $checkDayIndex <= $endDayIndex;
    }

    public function getDayAfterWeekend(): Range
    {
        $nextDayAfterWeekend = $this->end->englishDayOfWeek == DaysInWeek::SUNDAY->value ? $this->end->copy() : $this->end->copy()->next(DaysInWeek::SUNDAY->value)->setTime(8, 0);

        return new Range($nextDayAfterWeekend, $nextDayAfterWeekend->copy()->addDay());
    }

    public function getNightSpaces()
    {
        return [$this->getDayBeforeNight(), $this->getDayAfterNight()];
    }

    public function getDayBeforeNight(): Range
    {
        return new Range($this->start->copy()->subHours(12), $this->start->copy());
    }

    public function getDayAfterNight(): Range
    {
        return new Range($this->end->copy(), $this->end->copy()->addHours(12));
    }
}
