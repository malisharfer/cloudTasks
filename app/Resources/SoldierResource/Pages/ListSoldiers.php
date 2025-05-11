<?php

namespace App\Resources\SoldierResource\Pages;

use App\Models\Soldier;
use App\Models\Task;
use App\Resources\SoldierResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListSoldiers extends ListRecords
{
    protected static string $resource = SoldierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('Course editing')
                ->label(__('Edit course'))
                ->color('primary')
                ->form([
                    Section::make([
                        Select::make('course')
                            ->label(__('Course'))
                            ->options(Soldier::pluck('course', 'course')->sort()->unique()->all())
                            ->required(),
                    ]),
                    Section::make([
                        TextInput::make('max_shifts')
                            ->label(__('Max shifts'))
                            ->numeric()
                            ->step(1)
                            ->minValue(0),
                        TextInput::make('max_nights')
                            ->label(__('Max nights'))
                            ->numeric()
                            ->step(1)
                            ->minValue(0),
                        TextInput::make('max_weekends')
                            ->label(__('Max weekends'))
                            ->numeric()
                            ->step(0.25)
                            ->minValue(0),
                        TextInput::make('max_alerts')
                            ->label(__('Max alerts'))
                            ->numeric()
                            ->step(1)
                            ->minValue(0),
                        TextInput::make('max_in_parallel')
                            ->label(__('Max in parallel'))
                            ->numeric()
                            ->step(1)
                            ->minValue(0),
                        TextInput::make('capacity')
                            ->numeric()
                            ->step(0.25)
                            ->minValue(0)
                            ->label(__('Capacity')),
                        Select::make('qualifications')
                            ->label(__('Qualifications'))
                            ->multiple()
                            ->placeholder(__('Select qualifications'))
                            ->options(Task::all()->pluck('type', 'type')->sort()->unique()->all()),
                    ]),
                ])
                ->action(function (array $data) {
                    $selectedCourse = $data['course'];
                    $updateData = [];
                    $fields = ['max_shifts', 'max_nights', 'max_weekends', 'max_alerts', 'max_in_parallel', 'capacity', 'qualifications'];

                    foreach ($fields as $field) {
                        if (isset($data[$field]) && ! ($field === 'qualifications' && empty($data[$field]))) {
                            $updateData[$field] = $data[$field];
                        }
                    }
                    if (! empty($updateData)) {
                        $soldiers = Soldier::where('course', $selectedCourse)->get();
                        $soldiers->map(function ($soldier) use ($updateData) {
                            collect($updateData)->map(function ($value, $key) use ($soldier) {
                                $soldier->{$key} = $value;
                            });
                            $soldier->save();
                        });
                    }
                }),
        ];
    }
}
