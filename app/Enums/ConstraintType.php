<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ConstraintType: string implements HasLabel
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

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_WEEKEND => __('Not weekend'),
            self::LOW_PRIORITY_NOT_WEEKEND => __('Low priority not weekend'),
            self::NOT_TASK => __('Not task'),
            self::LOW_PRIORITY_NOT_TASK => __('Low priority not task'),
            self::NOT_EVENING => __('Not evening'),
            self::NOT_THURSDAY_EVENING => __('Not Thursday evening'),
            self::VACATION => __('Vacation'),
            self::MEDICAL => __('Medical'),
            self::SCHOOL => __('School'),
        };
    }

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
            self::NOT_EVENING => '#ffd4e5',
            self::NOT_THURSDAY_EVENING => '#ffdfba',
            self::NOT_WEEKEND => '#ffffba',
            self::LOW_PRIORITY_NOT_WEEKEND => '#adb2fb',
            self::VACATION => '#bae1ff',
            self::MEDICAL => '#f2d7fb',
            self::SCHOOL => '#f9a7a7',
            self::NOT_TASK => '#96ead7',
            self::LOW_PRIORITY_NOT_TASK => '#baffc9',
        };
    }
}
