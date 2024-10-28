<?php

namespace App\Services;

class Shift
{
    public $id;

    public $task_name;

    public $range;

    public $points;

    public $is_assign;

    public function __construct($id, string $task_name, $start, $end, float $points)
    {
        $this->id = $id;
        $this->task_name = $task_name;
        $this->range = new Range($start, $end);
        $this->points = $points;
        $this->is_assign = false;
    }

    protected function name(): string
    {
        return $this->task_name.': from'.$this->range->start.' to'.$this->range->end;
    }

    public function isWeekend(): bool
    {
        return $this->range->isWeekend();
    }

    public function isNight(): bool
    {
        if ($this->isWeekend()) {
            return false;
        }

        return $this->range->isNight();
    }
}