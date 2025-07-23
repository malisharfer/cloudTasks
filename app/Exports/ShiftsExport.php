<?php

namespace App\Exports;

use App\Models\Shift;
use App\Models\Task;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ShiftsExport implements WithMultipleSheets
{
    protected $month;

    protected $shifts;

    public function __construct($month)
    {
        $this->month = $month;
        $this->shifts = Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [Carbon::parse($this->month)->startOfMonth(), Carbon::parse($this->month)->endOfMonth()])
            ->get();
    }

    public function sheets(): array
    {
        $tasksTypes = Task::select('type', 'color')
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => $group->first())
            ->values()
            ->toArray();

        return collect($tasksTypes)->map(function ($type) {
            $shifts = $this->shifts->filter(fn (Shift $shift) => $shift->task()->withTrashed()->first()->type == $type['type']);

            return new TaskTypeSheet($this->month, $type['type'], $shifts, $type['color']);
        })->toArray();
    }
}
