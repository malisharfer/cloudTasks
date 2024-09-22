<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case DAILY = 'Daily';
    case WEEKLY = 'Weekly';
    case MONTHLY = 'Monthly';
    case CUSTOM = 'Custom';
    case ONETIME = 'OneTime';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
