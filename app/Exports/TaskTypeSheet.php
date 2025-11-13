<?php

namespace App\Exports;

use App\Models\Shift;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaskTypeSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected $month;

    protected $taskType;

    protected $shifts;

    protected $color;

    protected $tasksNames;

    public function __construct($month, $taskType, $shifts, $color)
    {
        $this->month = $month;
        $this->taskType = $taskType;
        $this->shifts = $shifts;
        $this->color = $this->convertColorToRBGFormat($color);
        $this->tasksNames = $this->getTasksNames();
    }

    protected function convertColorToRBGFormat($color)
    {
        $color = str_replace('#', '', $color);
        $color = strtoupper($color);

        return 'FF'.$color;
    }

    protected function getTasksNames()
    {
        $tasksNames = $this->shifts->groupBy(fn ($shift) => $shift->task()->withTrashed()->first()->type)
            ->map(fn ($shifts, $type) => $shifts->pluck('task.name')->unique())
            ->flatten();
        $tasksNamesWithoutWeekendAndThurthday = $tasksNames->filter(fn ($name) => strpos($name, 'סופש') !== false || strpos($name, 'חמישי') !== false)->flatten();
        $tasksNames = $tasksNames->filter(fn ($name) => (! strpos($name, 'סופש') && ! strpos($name, 'חמישי')))->flatten();

        $tasksNamesWithoutWeekendAndThurthday->each(function ($name) use (&$tasksNames) {
            $baseName = str_replace([' חמישי', ' סופש'], '', $name);
            if (! $tasksNames->contains($baseName)) {
                $tasksNames->push($name);
            }
        });

        return $tasksNames->toArray();
    }

    public function title(): string
    {
        return $this->taskType;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getTabColor()->setARGB($this->color);
        $sheet->setRightToLeft(true);
        $sheet->getStyle('A1:M'.$sheet->getHighestRow())
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $columnCount = count($this->tasksNames);
        $endColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount + 1);

        $sheet->getStyle('B1:'.$endColumn.'1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($this->color);

        $sheet->getStyle('B1:'.$endColumn.'1')
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THICK);

        $sheet->getStyle('B1:'.$endColumn.'1')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A2:A'.$sheet->getHighestRow())
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($this->color);

        $sheet->getStyle('A2:A'.$sheet->getHighestRow())
            ->getFont()
            ->setBold(true);

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $dateCell = $sheet->getCell('A'.$row)->getValue();
            $date = Carbon::parse($dateCell);
            if ($date->isFriday() || $date->isSaturday()) {
                $sheet->getStyle('A'.$row.':'.$endColumn.$row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('D3D3D3');
            }
        }
        $sheet->getStyle('A1:'.$endColumn.''.$sheet->getHighestRow())
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

    }

    public function headings(): array
    {
        return array_merge([' '], $this->tasksNames);
    }

    public function collection()
    {
        [$year, $month] = explode('-', $this->month);
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;

        $shiftsByDate = $this->shifts->groupBy(fn (Shift $shift) => $shift->start_date->format('Y-m-d'))->sortBy(fn ($shifts, $date) => $date);

        $data = collect(range(1, $daysInMonth))->map(function ($day) use ($year, $month, $shiftsByDate) {
            $date = Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
            $row = [$date];
            foreach ($this->tasksNames as $name) {
                $shiftForDate = $shiftsByDate->get($date, collect())->first(function ($shift) use ($name) {
                    $taskName = $shift->task()->withTrashed()->first()->name;
                    $baseName = str_replace([' סופש', ' חמישי'], '', $taskName);
                    $columnBase = str_replace([' סופש', ' חמישי'], '', $name);
                    if ($columnBase === $baseName) {
                        return true;
                    }

                    return false;
                });
                $row[] = ($shiftForDate && $shiftForDate->soldier) ? $shiftForDate->soldier->user->displayName : ' ';
            }

            return $row;
        });

        return $data;
    }
}
