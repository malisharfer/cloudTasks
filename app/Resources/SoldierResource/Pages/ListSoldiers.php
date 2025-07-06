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
                            ->options(Soldier::select('course')->distinct()->orderBy('course')->pluck('course', 'course')->all())
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
                            ->lte('max_shifts')
                            ->validationMessages([
                                'lte' => __('The field cannot be greater than max_shifts field'),
                            ])
                            ->minValue(0),
                        TextInput::make('max_weekends')
                            ->label(__('Max weekends'))
                            ->numeric()
                            ->step(0.25)
                            ->lte('capacity')
                            ->validationMessages([
                                'lte' => __('The field cannot be greater than capacity field'),
                            ])
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
                            ->options(Task::select('type')
                                ->distinct()
                                ->orderBy('type')
                                ->pluck('type', 'type')
                                ->all()),
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
                        $soldiers->map(function (Soldier $soldier) use ($updateData) {
                            collect($updateData)->map(function ($value, $key) use ($soldier) {
                                if ($key == 'qualifications') {
                                    $qualifications = collect($soldier->qualifications);
                                    $qualifications->push(...$value);
                                    $soldier->qualifications = $qualifications;
                                } else {
                                    $soldier->{$key} = $value;
                                }
                            });
                            $soldier->save();
                        });
                    }
                }),
        ];
    }
}
