<?php

namespace App\Services;

class ShiftData
{
    public $shift;

    public $weight;

    public $potentialSoldiers;

    public function __construct(Shift $shift, float $weight, $potentialSoldiers = [])
    {
        $this->shift = $shift;
        $this->weight = $weight;
        $this->potentialSoldiers = collect($potentialSoldiers);
    }
}
