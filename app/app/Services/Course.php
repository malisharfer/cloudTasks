<?php

namespace App\Services;

class Course
{
    public $number;

    public $max;

    public $maxName;

    public $soldiers;

    public $hasGap;

    public $used;

    public function __construct($number, $max, $maxName, $soldiers)
    {
        $this->number = $number;
        $this->max = $max;
        $this->maxName = $maxName;
        $this->soldiers = collect($soldiers);
        $this->hasGap = false;
        $this->used = $this->used();
    }

    public function remaining()
    {
        return collect($this->soldiers)->sum(fn (Soldier $soldier) => $soldier->{$this->maxName}->remaining());
    }

    private function used()
    {
        return $this->soldiers->min(fn (Soldier $soldier) => $soldier->{$this->maxName}->used);
    }
}
