<?php

namespace App\Enums;

enum RotationResult: int
{
    case SUCCESS = 1;
    case FAILED = 2;
    case SUCCESS_WITH_GAP = 3;
}
