<?php

namespace App\Exports;

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

    public function __construct($query, $month)
    {
        $this->query = $query;
        $this->month = $month;
    }

    public function collection()
    {
        return $this->query
            ->whereBetween('start_date', [Carbon::parse($this->month)->startOfMonth(), Carbon::parse($this->month)->endOfMonth()])
            ->sortBy('start_date')
            ->map(function ($shift) {
                $task = Task::find($shift->task_id);

                return [
                    __('Start date') => $shift->start_date,
                    __('End date') => $shift->end_date,
                    __('Soldier') => User::where('userable_id', $shift->soldier_id)->first()?->displayName ?? __('Unknown'),
                    __('Shift name') => $task->name,
                    __('Is night') => $task->is_night ? __('Yes') : __('No'),
                    __('Is weekend') => $task->is_weekend ? __('Yes') : __('No'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            __('Start date'),
            __('End date'),
            __('Soldier'),
            __('Shift name'),
            __('Is night'),
            __('Is weekend'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:F1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('D3D3D3');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return $this->month;
    }
}
