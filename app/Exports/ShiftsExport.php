<?php

namespace App\Exports;

use App\Models\Shift;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShiftsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected $query;

    protected $month;

    public function __construct($month)
    {
        $this->query = Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [Carbon::parse($this->month)->startOfMonth(), Carbon::parse($this->month)->endOfMonth()])
            ->get();
        $this->month = $month;
    }

    public function collection()
    {
        return $this->query
            ->sortBy('start_date')
            ->map(function ($shift) {
                $task = Task::find($shift->task_id);

                return [
                    __('Shift name') => $task->name,
                    __('Shift type') => $task->type,
                    __('Soldier') => User::where('userable_id', $shift->soldier_id)->first()?->displayName ?? __('Unknown'),
                    __('Start date') => $shift->start_date,
                    __('End date') => $shift->end_date,
                    __('Is night') => $task->is_night ? __('Yes') : __('No'),
                    __('Is weekend') => $task->is_weekend ? __('Yes') : __('No'),
                    __('Is alert') => $task->is_alert ? __('Yes') : __('No'),
                    __('In parallel') => $task->in_parallel ? __('Yes') : __('No'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            __('Shift name'),
            __('Shift type'),
            __('Soldier'),
            __('Start date'),
            __('End date'),
            __('Is night'),
            __('Is weekend'),
            __('Is alert'),
            __('In parallel'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:I1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D3D3D3');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return $this->month;
    }
}
