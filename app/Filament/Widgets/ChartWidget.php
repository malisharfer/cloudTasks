<?php

namespace App\Filament\Widgets;

use Filament\Support\RawJs;
use Filament\Widgets\BarChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Htmlable;


class ChartWidget extends BarChartWidget
{
    protected static ?string $heading = null;

    protected static ?string $pollingInterval = '0';
    
    public ?Collection $data;

    public ?string $headerLable;
    

    public function getHeading(): string | Htmlable | null
    {
        return $this->headerLable;
    }

    protected function getData(): array
    {
        $datasets = collect();
        $colors = ['#36A2EB', '#dc38e4', '#FF6384', '#FFCE56', '#36A2EB'];
    
        $this->data->each(function ($soldiers, $key) use ($datasets, $colors) {
            $datasets->push([
                'label' => 'Max '. (string)$key,
                'data' => $soldiers->values()->all(),
                'backgroundColor' => collect(range(0, $soldiers->count() - 1))->map(function () use ($datasets, $colors) {
                    return $colors[$datasets->count() % count($colors)];
                })->all(), 
                'borderColor' => $colors[$datasets->count() % count($colors)], 
                'labels' => $soldiers->keys()->all(),
            ]);
        });
    
        return [
            'datasets' => $datasets->all(),
            'labels' => collect(range(0, $this->data->max(fn($soldiers) => $soldiers->count()) - 1))->map(fn() => '')->all(),
        ];
    }

    protected function getOptions(): array|RawJs|null
    {
        return RawJs::make(<<<JS
        {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const soldierIndex = context.dataIndex; 
                            const soldierName = context.dataset.labels ? context.dataset.labels[soldierIndex] : 'Unknown Soldier';
                            const value = context.parsed.y; 
    
                            return soldierName + ': ' + value;
                        }
                    }
                }
            }
        }
        JS);
    }
}