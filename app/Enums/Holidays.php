<?php

namespace App\Enums;

enum Holidays: string
{
    case ROSH_ASHANA_1 = 'א תשרי';
    case ROSH_ASHANA_2 = 'ב תשרי';
    case YOM_KIPUR = 'י תשרי';
    case SUKOT = 'טו תשרי';
    case SIMCHAT_TORAH = 'כב תשרי';
    case PESACH = 'טו ניסן';
    case SHVIHI_SHEL_PESACH = 'כא ניסן';
    case SHAVUOT = 'ו סיון';

    public function getLabel(): string
    {
        return match ($this) {
            self::ROSH_ASHANA_1 => 'א דר"ה',
            self::ROSH_ASHANA_2 => 'ב דר"ה',
            self::YOM_KIPUR => 'יוה"כ',
            self::SUKOT => 'סוכות',
            self::SIMCHAT_TORAH => 'שמחת תורה',
            self::PESACH => 'פסח',
            self::SHVIHI_SHEL_PESACH => 'שביעי של פסח',
            self::SHAVUOT => 'שבועות'
        };
    }
}
