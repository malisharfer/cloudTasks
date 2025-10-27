<?php

namespace App\Exports;

use App\Models\Soldier;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssignmentJustice implements WithMultipleSheets
{
    public $month;

    public function __construct($month)
    {
        $this->month = $month;
    }

    public function sheets(): array
    {
        $courses = Soldier::select('course')->distinct()->orderByDesc('course')->pluck('course')->all();

        return collect($courses)->map(fn ($course) => new CourceSheet($this->month, $course))->toArray();
    }
}
