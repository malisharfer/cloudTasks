<?php

namespace App\Resources\ChartResource\Widgets;

use App\Enums\MonthesInYear;
use App\Exports\AssignmentJustice;
use App\Exports\ShiftsExport;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Services\Algorithm;
use App\Services\RecurringEvents;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Maatwebsite\Excel\Facades\Excel;

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
                    ->headerActions(
                        [
                            Action::make('Download')
                                ->label(__('Download to assignment'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->action(fn () => Excel::download(new ShiftsExport($form->getState()['year'].'-'.$form->getState()['month']), __('File name', [
                                    'name' => auth()->user()->displayName,
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ]).'.xlsx'))
                                ->extraAttributes(['style' => 'color: white; background-color: #a0cddf']),
                            Action::make('Download assignment justice')
                                ->label(__('Download the assignment justice'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->action(fn () => Excel::download(new AssignmentJustice($form->getState()['year'].'-'.$form->getState()['month']), __('File name', [
                                    'name' => auth()->user()->displayName,
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ]).'.xlsx'))
                                ->extraAttributes(['style' => 'color: white; background-color: #a0cddf']),
                            Action::make('Create shifts')
                                ->action(fn () => $this->runEvents($form))
                                ->label(__('Create shifts'))
                                ->icon('heroicon-o-clipboard-document-check')
                                ->visible(Task::withTrashed()->whereNull('deleted_at')->exists())
                                ->after(fn () => $this->successNotification(__('The shifts were created', [
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ])))
                                ->extraAttributes(['style' => 'color: white; background-color: #a0cddf']),
                            Action::make('Shifts assignment')
                                ->action(fn () => $this->runAlgorithm($form))
                                ->label(__('Shifts assignment'))
                                ->icon('heroicon-o-play')
                                ->visible(Task::withTrashed()->whereNull('deleted_at')->exists())
                                ->after(fn () => $this->successNotification(__('Shifts have been assigned', [
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ])))
                                ->extraAttributes(['style' => 'color: white; background-color: #a0cddf']),
                            Action::make('Reset assignment')
                                ->action(fn () => $this->resetShifts($form))
                                ->requiresConfirmation()
                                ->modalHeading(fn () => __('Reset assignment confirmation modal heading', [
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ]))
                                ->modalDescription(__('Are you sure? This cannot be undone!'))
                                ->modalSubmitActionLabel(__('Yes, reset it'))
                                ->after(fn () => $this->successNotification(__('The assignment has been reset', [
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ])))
                                ->color('danger')
                                ->label(__('Reset assignment'))
                                ->icon('heroicon-o-arrow-path'),
                            Action::make('Delete shifts')
                                ->color('danger')
                                ->action(fn () => $this->deleteShifts($form))
                                ->label(__('Delete shifts'))
                                ->requiresConfirmation()
                                ->modalHeading(fn () => __('Delete shifts confirmation modal heading', [
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ]))
                                ->after(fn () => $this->successNotification(__('The shifts have been deleted', [
                                    'month' => $form->getState()['year'].'-'.$form->getState()['month'],
                                ])))
                                ->icon('heroicon-o-x-circle'),
                        ]
                    )
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

    protected function successNotification($title)
    {
        return Notification::make()
            ->success()
            ->title($title)
            ->persistent()
            ->send();
    }

    protected function runEvents($form)
    {
        $recurringEvents = new RecurringEvents($form->getState()['year'].'-'.$form->getState()['month']);
        $recurringEvents->recurringTask();
    }

    protected function runAlgorithm($form)
    {
        $algorithm = new Algorithm($form->getState()['year'].'-'.$form->getState()['month']);
        $algorithm->run();
    }

    protected function resetShifts($form)
    {
        $startDate = 
        // (Carbon::now()->format('m') == Carbon::parse($form->getState()['year'].'-'.$form->getState()['month'])->format('m'))
        //     ? Carbon::now()->addDay()->format('Y-m-d')
        //     :
             Carbon::parse($form->getState()['year'].'-'.$form->getState()['month'])->startOfMonth()->format('Y-m-d');
        Shift::whereNotNull('soldier_id')
            ->whereBetween('start_date', [$startDate, (Carbon::parse($form->getState()['year'].'-'.$form->getState()['month'])->endOfMonth()->addDay())->format('Y-m-d')])
            ->update(['soldier_id' => null]);
    }

    protected function deleteShifts($form)
    {
        $startDate = 
        // (Carbon::now()->format('m') == Carbon::parse($form->getState()['year'].'-'.$form->getState()['month'])->format('m'))
        //     ? Carbon::now()->addDay()->format('Y-m-d')
        //     : 
            Carbon::parse($form->getState()['year'].'-'.$form->getState()['month'])->startOfMonth()->format('Y-m-d');
        Shift::whereBetween('start_date', [$startDate, (Carbon::parse($form->getState()['year'].'-'.$form->getState()['month'])->endOfMonth()->addDay())->format('Y-m-d')])
            ->delete();
    }

    protected static function getYearOptions(): array
    {
        $currentYear = now()->year;
        $years = range($currentYear - 1, $currentYear + 4);

        return array_combine($years, $years);
    }
}
