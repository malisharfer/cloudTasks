<?php

namespace App\Exports;

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CourceSheet implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public $month;

    public $course;

    public $shifts;

    public function __construct($month, $course)
    {
        $this->month = $month;
        $this->course = $course;
        $this->shifts = Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [Carbon::parse($this->month)->startOfMonth(), Carbon::parse($this->month)->endOfMonth()])
            ->whereHas('soldier', function ($query) {
                $query->where('course', $this->course);
            })->get();
    }

    public function title(): string
    {
        return __('Course').' '.$this->course;
    }

    public function headings(): array
    {
        return [
            [
                ' ',
                __('Points'),
                ' ',
                __('Weekends'),
                ' ',
                __('Nights'),
                ' ',
                __('Regulars'),
                ' ',
                __('Alerts'),
                ' ',
                __(key: 'In parallels'),
                ' ',
            ],
            [
                __('Full name'),
                __('Max'),
                __('Done'),
                __('Max'),
                __('Done'),
                __('Max'),
                __('Done'),
                __('Max'),
                __('Done'),
                __('Max'),
                __('Done'),
                __('Max'),
                __('Done'),
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {
                collect([
                    'B1:C1',
                    'D1:E1',
                    'F1:G1',
                    'H1:I1',
                    'J1:K1',
                    'L1:M1',
                ])->each(function ($range) use ($event) {
                    $event->sheet->mergeCells($range);
                });
                $event->sheet->getDelegate()->freezePane('A2');
                $event->sheet->getDelegate()->freezePane('A3');
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $this->applayColors($sheet);
        $this->applySheetFormatting($sheet);
    }

    protected function applayColors(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $this->italicizeSoldiersWithNoQualifications($sheet);
        $this->colorCellsByValue($sheet, $highestRow);

        $sheet->getStyle('A3:A'.$highestRow)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('d6d9d4');

        $sheet->getStyle('A1:M2')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('d6d9d4');
    }

    protected function italicizeSoldiersWithNoQualifications(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = 3; $row <= $highestRow; $row++) {
            collect(...Soldier::whereHas('user', function ($query) use ($sheet, $row) {
                $query->whereRaw("first_name || ' ' || last_name = ?", [$sheet->getCellByColumnAndRow(1, $row)->getValue()]);
            })->pluck('qualifications'))->count() == 0 ?
                $sheet->getStyleByColumnAndRow(1, $row)
                    ->getFont()
                    ->setItalic(true)
                    ->setColor(new Color('b1bcbf'))
                : null;
        }
    }

    protected function colorCellsByValue($sheet, $highestRow)
    {
        $colors = [
            'eef0ed',
            'fdf3d1',
            'ebfcef',
            'fbebfc',
            'fcebef',
            'ebfbfc',
            'dcdbdb',
            'dfd8ff',
            'afc4ff',
            'e5a5dc',
            'f7b2cb',
            'd2b3ae',
        ];
        $columnsIndexes = collect([2, 4, 6, 8, 10, 12]);

        $columnsIndexes->each(function ($column) use ($sheet, $highestRow, $colors) {
            $columnData = collect();
            for ($row = 3; $row <= $highestRow; $row++) {
                $columnData->push($sheet->getCellByColumnAndRow($column, $row)->getValue());
            }
            $valueCounts = $columnData->countBy();
            $sortedValues = $valueCounts->sortDesc()->keys();
            $colorMap = $sortedValues->mapWithKeys(function ($item, $index) use ($colors) {
                return [$item => $colors[$index % count($colors)]];
            });
            for ($row = 3; $row <= $highestRow; $row++) {
                $cellValue = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                if ($colorMap->has($cellValue)) {
                    $sheet->getStyleByColumnAndRow($column, $row)->getFill()->setFillType(Fill::FILL_SOLID);
                    $sheet->getStyleByColumnAndRow($column, $row)->getFill()->getStartColor()->setARGB($colorMap[$cellValue]);
                }
            }
        });
    }

    protected function applySheetFormatting(Worksheet $sheet)
    {
        $sheet->setRightToLeft(true);

        $sheet->getStyle('A1:M'.$sheet->getHighestRow())
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle('A1:M'.$sheet->getHighestRow())
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A3:A'.$sheet->getHighestRow())
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A1:M2')
            ->getFont()
            ->setBold(true);
    }

    public function collection()
    {
        $soldiers = Soldier::where('course', $this->course)->get();

        return $soldiers->map(function (Soldier $soldier) {
            $shifts = $this->shifts->filter(fn (Shift $shift) => $shift->soldier_id == $soldier->id);

            return [
                $soldier->user->displayName,
                $soldier->capacity,
                $this->howMuchPoints($shifts),
                $soldier->max_weekends,
                $this->howMuchWeekends($shifts),
                $soldier->max_nights,
                $this->howMuchBy(TaskKind::NIGHT->value, $shifts),
                $soldier->max_shifts,
                $this->howMuchBy(TaskKind::REGULAR->value, $shifts),
                $soldier->max_alerts,
                $this->howMuchBy(TaskKind::ALERT->value, $shifts),
                $soldier->max_in_parallel,
                $this->howMuchBy(TaskKind::INPARALLEL->value, $shifts),
            ];
        });
    }

    protected function howMuchPoints($shifts)
    {
        return $shifts
            ->sum(fn (Shift $shift) => $shift->parallel_weight != null ? $shift->parallel_weight : $shift->task()->withTrashed()->first()->parallel_weight);
    }

    protected function howMuchWeekends($shifts)
    {
        return $shifts
            ->filter(fn (Shift $shift) => $shift->is_weekend != null ? $shift->is_weekend : ($shift->task()->withTrashed()->first()->kind == TaskKind::WEEKEND->value))
            ->sum(fn (Shift $shift) => $shift->parallel_weight != null ? $shift->parallel_weight : $shift->task()->withTrashed()->first()->parallel_weight);
    }

    protected function howMuchBy($taskKind, $shifts)
    {
        return $shifts
            ->filter(fn (Shift $shift) => $shift->task()->withTrashed()->first()->kind == $taskKind)
            ->count();
    }
}
