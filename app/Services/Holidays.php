<?php

namespace App\Services;

use App\Enums\Holidays as HolidayEnum;

class Holidays
{
    public $isHoliday;

    public $holidayName;

    public function __construct($month, $day, $year)
    {
        $this->isHoliday = $this->jewishDate($month, $day, $year);
    }

    public function jewishDate($month, $day, $year): bool
    {
        $dayAndMonth = implode(' ', array_slice(
            explode(' ', iconv('WINDOWS-1255', 'UTF-8', jdtojewish(
                gregoriantojd($month, $day, $year), true)
            )), 0, 2
        ));

        $this->setHolidayName($dayAndMonth);

        return HolidayEnum::tryFrom($dayAndMonth) !== null;
    }

    public function setHolidayName(string $date): void
    {
        $this->holidayName = HolidayEnum::tryFrom($date)?->getLabel();
    }
}
