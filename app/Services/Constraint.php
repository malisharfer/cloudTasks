<?php

namespace App\Services;

use App\Enums\Priority;

class Constraint
{
    public $range;

    public $priority;

    public function __construct($start, $end, Priority $priority = Priority::HIGH)
    {
        $this->range = new Range($start, $end);
        $this->priority = $priority;
    }
}