<?php

namespace App\Resources\ChartResource\Widgets;

use App\Enums\MonthesInYear;
use App\Models\Soldier;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;

class ChartFilter extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'resources.chart-resource.widgets.chart-filter';

    public ?array $data = [];

    public function getColumnSpan(): int|string|array
    {
        return 2;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->key(2)
                    ->schema([
                        Select::make('course')
                            ->default(Soldier::select('course')->distinct()->orderBy('course')->pluck('course')->last())
                            ->options(Soldier::select('course')->distinct()->orderBy('course')->pluck('course', 'course')->all())
                            ->placeholder(__('Select course'))
                            ->label(__('Course'))
                            ->reactive(),
                        Select::make('year')
                            ->default(now()->addMonth()->year)
                            ->label(__('Year'))
                            ->options(self::getYearOptions())
                            ->placeholder(__('Select year')),
                        Select::make('month')
                            ->default(now()->addMonth()->format('m'))
                            ->label(__('Month'))
                            ->options(collect(MonthesInYear::cases())->mapWithKeys(fn ($month) => [$month->value => $month->getLabel()]))
                            ->placeholder(__('Select month')),
                    ])
                    ->columns(3)
                    ->footerActions(
                        [
                            Action::make(__('Filter'))
                                ->extraAttributes(['style' => 'color: white; background-color: #a0cddf'])
                                ->action(function () use ($form) {
                                    $this->dispatch('refreshChartData', $form->getState()['course'], $form->getState()['month'], $form->getState()['year']);
                                }),
                        ]
                    ),
            ])
            ->statePath('data');
    }

    protected static function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = range($currentYear - 1, $currentYear + 4);

        return array_combine($years, $years);
    }
}
