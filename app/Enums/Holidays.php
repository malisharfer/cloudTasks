<?php

namespace App\Enums;

enum Holidays: string
{
    case ROSH_ASHANA_1 = 'א תשרי';
    case ROSH_ASHANA_2 = 'ב תשרי';
    case YOM_KIPUR = 'י תשרי';
    case SUKOT = 'טו תשרי';
    case CHOL_HAMOHED_SUKOT_1 = 'טז תשרי';
    case CHOL_HAMOHED_SUKOT_2 = 'יז תשרי';
    case CHOL_HAMOHED_SUKOT_3 = 'יח תשרי';
    case CHOL_HAMOHED_SUKOT_4 = 'יט תשרי';
    case CHOL_HAMOHED_SUKOT_5 = 'כ תשרי';
    case HOSHAANA_RABA = 'כא תשרי';
    case SIMCHAT_TORAH = 'כב תשרי';
    case PESACH = 'טו ניסן';
    case CHOL_HAMOHED_PESACH_1 = 'טז ניסן';
    case CHOL_HAMOHED_PESACH_2 = 'יז ניסן';
    case CHOL_HAMOHED_PESACH_3 = 'יח ניסן';
    case CHOL_HAMOHED_PESACH_4 = 'יט ניסן';
    case CHOL_HAMOHED_PESACH_5 = 'כ ניסן';
    case SHVIHI_SHEL_PESACH = 'כא ניסן';
    case SHAVUOT = 'ו סיון';

    public function getLabel(): string
    {
        return match ($this) {
            self::ROSH_ASHANA_1 => 'א דר"ה',
            self::ROSH_ASHANA_2 => 'ב דר"ה',
            self::YOM_KIPUR => 'יוה"כ',
            self::SUKOT => 'סוכות',
            self::CHOL_HAMOHED_SUKOT_1 => 'א דחוה"מ סוכות',
            self::CHOL_HAMOHED_SUKOT_2 => 'ב דחוה"מ סוכות',
            self::CHOL_HAMOHED_SUKOT_3 => 'ג דחוה"מ סוכות',
            self::CHOL_HAMOHED_SUKOT_4 => 'ד דחוה"מ סוכות',
            self::CHOL_HAMOHED_SUKOT_5 => 'ה דחוה"מ סוכות',
            self::HOSHAANA_RABA => 'הושענא רבה',
            self::SIMCHAT_TORAH => 'שמחת תורה',
            self::PESACH => 'פסח',
            self::CHOL_HAMOHED_PESACH_1 => 'א דחוה"מ פסח',
            self::CHOL_HAMOHED_PESACH_2 => 'ב דחוה"מ פסח',
            self::CHOL_HAMOHED_PESACH_3 => 'ג דחוה"מ פסח',
            self::CHOL_HAMOHED_PESACH_4 => 'ד דחוה"מ פסח',
            self::CHOL_HAMOHED_PESACH_5 => 'ה דחוה"מ פסח',
            self::SHVIHI_SHEL_PESACH => 'שביעי של פסח',
            self::SHAVUOT => 'שבועות'
        };
    }
}
