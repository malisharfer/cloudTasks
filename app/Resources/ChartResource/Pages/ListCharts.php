<?php

namespace App\Resources\ChartResource\Pages;

use App\Enums\TaskKind;
use App\Filament\Widgets\ChartWidget;
use App\Models\Soldier;
use App\Resources\ChartResource;
use App\Resources\ChartResource\Widgets\ChartFilter;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCharts extends ListRecords
{
    protected static string $resource = ChartResource::class;

    protected function getHeaderWidgets(): array
    {
        return [ChartFilter::class];
    }

    protected function getFooterWidgets(): array
    {
        return [...$this->getChartWidgets()];
    }

    protected function getChartWidgets(): array
    {
        $kinds = collect([
            'points',
            TaskKind::WEEKEND->value,
            TaskKind::NIGHT->value,
            TaskKind::REGULAR->value,
            TaskKind::ALERT->value,
            TaskKind::INPARALLEL->value,
        ]);

        return $kinds->map(fn ($kind) => ChartWidget::make([
            'kind' => $kind,
            'month' => now()->addMonth()->month,
            'year' => now()->addMonth()->year,
            'course' => Soldier::select('course')->distinct()->orderBy('course')->pluck('course')->last(),
        ]))->toArray();
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getBreadcrumb(): string
    {
        return '';
    }
}
