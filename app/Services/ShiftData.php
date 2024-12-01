<?php

namespace App\Services;

class ShiftData
{
    public $shift;

    public $potentialSoldiers;

    public $weight;

    public function __construct(Shift $shift, $potentialSoldiers, float $weight)
    {
        $this->shift = $shift;
        $this->potentialSoldiers = collect($potentialSoldiers);
        $this->weight = $weight;
    }
}