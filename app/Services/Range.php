<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;

class Range
{
    public $start;

    public $end;

    public function __construct($start, $end)
    {
        if ($start > $end) {
            new Exception('Invalid range');
        }
        $this->start = $start;
        $this->end = $end;
    }

    public function isConflict(Range $other): bool
    {
        return ! (Carbon::parse($this->start)->isAfter(Carbon::parse($other->end)) || Carbon::parse($this->end)->isBefore(Carbon::parse($other->start)));
    }

    public function isWeekend(): bool
    {
        return (
            ($this->start->dayOfWeek == 4 && $this->start->hour >= 20)
            || $this->start->dayOfWeek == 5
            || $this->start->dayOfWeek == 6
            || ($this->start->dayOfWeek == 0 && $this->start->hour < 8)
        )
            ||
            (
                ($this->end->dayOfWeek == 4 && $this->end->hour >= 20)
                || $this->end->dayOfWeek == 5
                || $this->end->dayOfWeek == 6
                || ($this->end->dayOfWeek == 0 && $this->end->hour < 8)
            )
            ||
            $this->start->diffInDays($this->end) > 5;
    }

    public function isNight(): bool
    {
        if ($this->isWeekend()) {
            return false;
        }

        return
            (
                ($this->start->day == $this->end->day)
                && (
                    ($this->start->hour >= 00 && $this->start->hour < 8)
                    || $this->start->hour >= 20
                    || $this->end->hour > 20
                )
            )
            ||
            $this->start->day < $this->end->day;
    }

    public function isSameMonth(Range $other): bool
    {
        return $other->start->monthName == $this->start->monthName
            || $other->start->monthName == $this->end->monthName
            || $other->end->monthName == $this->start->monthName
            || $other->end->monthName == $this->end->monthName;
    }
}