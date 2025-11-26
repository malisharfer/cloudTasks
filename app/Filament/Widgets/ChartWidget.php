<?php

namespace App\Filament\Widgets;

use App\Enums\TaskKind;
use App\Services\Charts;
use Filament\Support\RawJs;
use Filament\Widgets\BarChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class ChartWidget extends BarChartWidget
{
    protected static ?string $heading = null;

    protected static ?string $pollingInterval = '0';

    public $month;

    public $year;

    public $course;

    public $kind;

    public function getHeading(): string|Htmlable|null
    {
        return match ($this->kind) {
            'points' => __('Points'),
            TaskKind::WEEKEND->value => TaskKind::from(TaskKind::WEEKEND->value)->getLabel(),
            TaskKind::NIGHT->value => TaskKind::from(TaskKind::NIGHT->value)->getLabel(),
            TaskKind::REGULAR->value => TaskKind::from(TaskKind::REGULAR->value)->getLabel(),
            TaskKind::ALERT->value => TaskKind::from(TaskKind::ALERT->value)->getLabel(),
            TaskKind::INPARALLEL->value => TaskKind::from(TaskKind::INPARALLEL->value)->getLabel(),
        };
    }

    protected function getListeners(): array
    {
        return [
            'refreshChartData' => 'refreshCharts',
        ];
    }

    public function refreshCharts($course, $month, $year)
    {
        $this->course = $course;
        $this->month = $month;
        $this->year = $year;
        $this->getData();
    }

    protected function getData(): array
    {
        $chart = new Charts($this->course, $this->year, $this->month, $this->kind);
        $data = $chart->getData();
        $datasets = collect();
        $colors = ['#a0cddf', '#FF6384', '#b0cfa1', '#36A2EB', '#92d694', '#FFCE56', '#dc38e4', '#36A2EB'];
        $data->each(function ($soldiers, $key) use ($datasets, $colors) {
            $datasets->push([
                'label' => __('Max').' '.(string) $key,
                'data' => $soldiers->values()->all(),
                'backgroundColor' => collect(range(0, $soldiers->count() - 1))->map(function () use ($datasets, $colors) {
                    return $colors[$datasets->count() % count($colors)];
                })->all(),
                'borderColor' => $colors[$datasets->count() % count($colors)],
                'labels' => $soldiers->keys()->all(),
                'minBarLength' => 5,
            ]);
        });

        return [
            'datasets' => $datasets->all(),
            'labels' => collect(range(0, $data->max(fn ($soldiers) => $soldiers->count()) - 1))->map(fn () => '')->all(),
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<'JS'
        {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const soldierIndex = context.dataIndex;
                            const soldierName = context.dataset.labels ? context.dataset.labels[soldierIndex] : __('Unknown Soldier');
                            const value = context.parsed.y;

                            return soldierName + ': ' + value;
                        }
                    }
                },
            }
        }
        JS);
    }
}
