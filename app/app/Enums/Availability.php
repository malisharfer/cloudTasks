<?php

namespace App\Enums;

enum Availability: int
{
    case YES = 1;
    case NO = 2;
    case BETTER_NOT = 3;
}
