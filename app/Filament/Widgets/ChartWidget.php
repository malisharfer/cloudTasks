<?php

namespace App\Filament\Widgets;

use App\Enums\MonthesInYear;
use App\Models\Soldier;
use App\Services\Charts;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ChartWidget extends ApexChartWidget
{
    protected static ?int $sort = 3;

    protected static ?int $contentHeight = 500;

    protected function getFormSchema(): array
    {
        return [
            Radio::make('ChartType')
                ->default('bar')
                ->options([
                    'line' => __('Line'),
                    'bar' => __('Col'),
                    'area' => __('Area'),
                ])
                ->inline(true)
                ->label(__('Type')),
            Grid::make()
                ->schema([
                    Toggle::make('ordersChartMarkers')
                        ->default(false)
                        ->label(__('Markers')),

                    Toggle::make('ordersChartGrid')
                        ->default(false)
                        ->label(__('Grid')),
                ]),
            Select::make('data')
                ->default('points')
                ->hiddenLabel()
                ->placeholder(__('Select parameter to filter'))
                ->live()
                ->options([
                    'shifts' => __('Shifts'),
                    'lowConstraintsRejected' => __('low Constraints Rejected'),
                    'constraints' => __('Constraints'),
                    'points' => __('Points'),
                    'weekends' => __('Weekends'),
                    'nights' => __('Nights'),
                ]),
            Select::make('year')
                ->default(now()->year)
                ->label(__('Year'))
                ->options($this->getYearOptions())
                ->placeholder(__('Select year')),
            Select::make('month')
                ->default(12)
                ->label(__('Month'))
                ->options($this->getMonthOptions())
                ->placeholder(__('Select month')),
            Select::make('course')
                ->default(1)
                ->options(
                    Soldier::pluck('course', 'course')->sort()->unique()->all()
                )
                ->placeholder(__('Select course'))
                ->label(__('Course')),
        ];
    }

    protected function getOptions(): array
    {
        $filters = $this->filterFormData;
        $chart = new Charts;
        $detailes = $chart->organizeChartData($filters['data'], $filters['course'], $filters['month'], $filters['year']);

        return [
            'chart' => [
                'type' => $filters['ChartType'],
                'height' => 490,
                'toolbar' => [
                    'show' => true,
                ],
            ],
            'theme' => [
                'mode' => 'light',
            ],
            'series' => [
                [
                    'name' => __('label', ['data' => __($filters['data']), 'course' => $filters['course']]),
                    'data' => $detailes['data'],
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 2,
                ],
            ],
            'xaxis' => [
                'categories' => $detailes['labels'],
                'labels' => [
                    'show' => false,
                ],
            ],
            'tooltip' => [
                'enabled' => true,
                'x' => [
                    'formatter' => function ($val, $index) use ($detailes) {
                        return $detailes['labels'][$index] ?? $val;
                    },
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontWeight' => 400,
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shade' => 'dark',
                    'type' => 'vertical',
                    'shadeIntensity' => 0.5,
                    'gradientToColors' => ['#fbbf24'],
                    'inverseColors' => true,
                    'opacityFrom' => 1,
                    'opacityTo' => 1,
                    'stops' => [0, 100],
                ],
            ],

            'dataLabels' => [
                'enabled' => false,
                'dropShadow' => true,
            ],
            'grid' => [
                'show' => $filters['ordersChartGrid'],
            ],
            'tooltip' => [
                'enabled' => true,
            ],
            'stroke' => [
                'width' => $filters['ChartType'] === 'line' ? 4 : 0,
            ],
            'noData' => [
                'text' => __('No matching data!'),
                'align' => 'center',
                'verticalAlign' => 'middle',
                'offsetX' => 0,
                'offsetY' => 0,
                'style' => [
                    'color' => '#FF0000',
                    'fontSize' => '17px',
                    'fontWeight' => 400,
                    'fontFamily' => 'inherit',
                ],
            ],
            'colors' => ['#f59e0b'],
        ];
    }

    protected function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = range($currentYear - 1, $currentYear + 4);

        return array_combine($years, $years);
    }

    protected function getMonthOptions(): array
    {
        return array_combine(
            array_map(fn ($enum) => $enum->value, MonthesInYear::cases()),
            array_map(fn ($enum) => $enum->getLabel(), MonthesInYear::cases())
        );
    }
}
