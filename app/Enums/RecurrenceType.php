<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case DAILY = 'Daily';
    case WEEKLY = 'Weekly';
    case MONTHLY = 'Monthly';
    case CUSTOM = 'Custom';
    case ONETIME = 'OneTime';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => __('Daily'),
            self::WEEKLY => __('Weekly'),
            self::MONTHLY => __('Monthly'),
            self::CUSTOM => __('Custom'),
            self::ONETIME => __('OneTime'),
        };
    }
}
