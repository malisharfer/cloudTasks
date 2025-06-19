<?php

namespace App\Enums;

enum RecurringType: string
{
    case DAILY = 'Daily';
    case WEEKLY = 'Weekly';
    case MONTHLY = 'Monthly';
    case CUSTOM = 'Custom';
    case ONETIME = 'One time';
    case DAILY_RANGE = 'Daily range';

    public function getLabel(): string
    {
        return match ($this) {
            self::DAILY => __('Daily'),
            self::WEEKLY => __('Weekly'),
            self::MONTHLY => __('Monthly'),
            self::CUSTOM => __('Custom'),
            self::ONETIME => __('One time'),
            self::DAILY_RANGE => __('Daily range'),
        };
    }
}
