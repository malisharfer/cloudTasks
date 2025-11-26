<?php

namespace App\Services;

class Course
{
    public $number;

    public $max;

    public $soldiers;

    public $hasGap;
   
    public $used;

    public function __construct($number, $max, $soldiers)
    {
        $this->number = $number;
        $this->max = $max;
        $this->soldiers = collect($soldiers);
        $this->hasGap = false;
        $this->used = 0;
    }

    public function remaining($max)
    {
        return collect($this->soldiers)->sum(fn (Soldier $soldier) => $soldier->{$max}->remaining());
    }
}