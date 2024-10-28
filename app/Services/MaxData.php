<?php

namespace App\Services;

class MaxData
{
    public $max;

    public $used;

    public function __construct(float $max, float $used = 0)
    {
        $this->max = $max;
        $this->used = $used;
    }

    public function remaining(): float
    {
        return $this->max - $this->used;
    }

    public function relativeLoad(): float
    {
        if ($this->max == 0) {
            return 0;
        }

        return $this->used / $this->max;
    }
}