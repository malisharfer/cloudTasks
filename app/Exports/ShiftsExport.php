<?php

namespace App\Exports;

use App\Models\Shift;
use App\Models\Task;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ShiftsExport implements WithMultipleSheets
{
    protected $month;

    protected $query;

    public function __construct($month)
    {
        $this->month = $month;
        $this->query = Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [Carbon::parse($this->month)->startOfMonth(), Carbon::parse($this->month)->endOfMonth()]);
    }

    public function sheets(): array
    {
        set_time_limit(0);
        $tasksTypes = Task::select('type', 'color')
            ->get()
            ->groupBy('type')
            ->map(fn ($group) => $group->first())
            ->values()
            ->toArray();

        return collect($tasksTypes)->map(function ($type) {
            $shifts = $this->query->clone()->whereHas('task', function ($query) use ($type) {
                $query->withTrashed()->where('type', $type['type']);
            })->get();

            return new TaskTypeSheet($this->month, $type['type'], $shifts, $type['color']);
        })->toArray();
    }
}
