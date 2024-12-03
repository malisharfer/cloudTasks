<?php

namespace App\Services;

class Assignment
{
    public $shiftId;

    public $soldierId;

    public function __construct(int $shiftId, int $soldierId)
    {
        $this->shiftId = $shiftId;
        $this->soldierId = $soldierId;
    }
}
