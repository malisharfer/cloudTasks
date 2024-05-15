<?php

namespace App\Enums\Requests;

enum ServiceType: string
{
    case Regular = 'regular';
    case Reserve = 'reserve';
    case Consultant = 'consultant';
    case External = 'external';
}
