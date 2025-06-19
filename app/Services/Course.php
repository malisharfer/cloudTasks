<?php

namespace App\Services;

class Course
{
    public $number;

    public $max;

    public $soldiers;

    public function __construct($number, $max, $soldiers)
    {
        $this->number = $number;
        $this->max = $max;
        $this->soldiers = collect($soldiers);
    }

    public function remaining($max)
    {
        return collect($this->soldiers)->sum(fn (Soldier $soldier) => $soldier->{$max}->remaining());
    }
}
