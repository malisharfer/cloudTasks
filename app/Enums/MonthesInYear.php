<?php

namespace App\Enums;

enum MonthesInYear: string
{
    case JANUARY = '01';
    case FEBRUARY = '02';
    case MARCH = '03';
    case APRIL = '04';
    case MAY = '05';
    case JUNE = '06';
    case JULY = '07';
    case AUGUST = '08';
    case SEPTEMBER = '09';
    case OCTOBER = '10';
    case NOVEMBER = '11';
    case DECEMBER = '12';

    public function getLabel(): string
    {
        return match ($this) {
            self::JANUARY => __('January'),
            self::FEBRUARY => __('February'),
            self::MARCH => __('March'),
            self::APRIL => __('April'),
            self::MAY => __('May'),
            self::JUNE => __('June'),
            self::JULY => __('July'),
            self::AUGUST => __('August'),
            self::SEPTEMBER => __('September'),
            self::OCTOBER => __('October'),
            self::NOVEMBER => __('November'),
            self::DECEMBER => __('December'),
        };
    }
}
