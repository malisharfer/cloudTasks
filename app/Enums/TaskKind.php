<?php

namespace App\Enums;

enum TaskKind: string
{
    case REGULAR = 'Regular';
    case ALERT = 'Alert';
    case NIGHT = 'Night';
    case WEEKEND = 'Weekend';
    case INPARALLEL = 'In parallel';

    public function getLabel(): string
    {
        return match ($this) {
            self::REGULAR => __('Regular'),
            self::ALERT => __('Alert'),
            self::NIGHT => __('Is night'),
            self::WEEKEND => __('Is weekend'),
            self::INPARALLEL => __('In parallel'),
        };
    }
}
