<?php

namespace App\Enums;

enum ConstraintType: string
{
    case NOT_WEEKEND = 'Not weekend';
    case LOW_PRIORITY_NOT_WEEKEND = 'Low priority not weekend';
    case NOT_TASK = 'Not task';
    case LOW_PRIORITY_NOT_TASK = 'Low priority not task';
    case NOT_EVENING = 'Not evening';
    case NOT_THURSDAY_EVENING = 'Not Thursday evening';
    case VACATION = 'Vacation';
    case MEDICAL = 'Medical';
    case SCHOOL = 'School';

    public static function getPriority(): array
    {
        return [
            self::NOT_EVENING->value => 1,
            self::NOT_THURSDAY_EVENING->value => 1,
            self::NOT_WEEKEND->value => 1,
            self::LOW_PRIORITY_NOT_WEEKEND->value => 2,
            self::VACATION->value => 1,
            self::MEDICAL->value => 1,
            self::SCHOOL->value => 1,
            self::NOT_TASK->value => 1,
            self::LOW_PRIORITY_NOT_TASK->value => 2,
        ];
    }

    public static function getLimit(): array
    {
        return [
            self::NOT_EVENING->value => 4,
            self::NOT_THURSDAY_EVENING->value => 1,
            self::NOT_WEEKEND->value => 1,
            self::LOW_PRIORITY_NOT_WEEKEND->value => 1,
            self::VACATION->value => 0,
            self::MEDICAL->value => 0,
            self::SCHOOL->value => 4,
            self::NOT_TASK->value => 3,
            self::LOW_PRIORITY_NOT_TASK->value => 3,
        ];
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NOT_EVENING => '#FFCBCB',
            self::NOT_THURSDAY_EVENING => '#FF9AFF',
            self::NOT_WEEKEND => '#D7D7D7',
            self::LOW_PRIORITY_NOT_WEEKEND => '#B0CEFF',
            self::VACATION => '#B0F8FF',
            self::MEDICAL => '#B0FFDD',
            self::SCHOOL => '#BBFFB0',
            self::NOT_TASK => '#E0FFB0',
            self::LOW_PRIORITY_NOT_TASK => '#FFD6B0',
        };
    }
}
