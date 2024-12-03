<?php

namespace App\Services;

class PotentialSoldierData
{
    public $soldierId;

    public $isLowConstraint;

    public function __construct(int $soldierId, bool $isLowConstraint)
    {
        $this->soldierId = $soldierId;
        $this->isLowConstraint = $isLowConstraint;
    }
}
