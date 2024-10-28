<?php

namespace App\Models;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d',
        'end_date' => 'datetime:Y-m-d',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getTaskNameAttribute()
    {
        $user_name = User::where('userable_id', $this->soldier_id)->get(['first_name', 'last_name']);

        return $this->task?->name . ' ' . $user_name->first()?->first_name . ' ' . $user_name->first()?->last_name;
    }

    public function getTaskColorAttribute()
    {
        return $this->task?->color;
    }

    public static function getSchema(): array
    {
        return [
            Section::make([
                Placeholder::make('')
                    ->content(content: fn(Shift $shift) => $shift->task_name)
                    ->inlineLabel(),
                Grid::make()
                    ->schema([
                        ToggleButtons::make('soldier_type')
                            ->required()
                            ->label(__('Soldier'))
                            ->reactive()
                            ->live()
                            ->inline()
                            ->options(
                                fn(?Shift $shift) => self::getOptions($shift->task->department_name)
                            ),
                        Select::make('soldier_id')
                            ->label('Soldier assignment')
                            ->options(
                                function (?Shift $shift, Get $get) {
                                    $manual_assignment = new \App\Services\ManualAssignment($shift, $get('soldier_type'));

                                    return $manual_assignment->getSoldiers();
                                }
                            )
                            ->default(null)
                            ->placeholder('Select soldier')
                            ->visible(
                                fn(Get $get): bool => $get('soldier_type') != null
                            ),
                    ])
                    ->visible(
                        fn(?Shift $record, Get $get): bool => $record !== null
                        && !$record->soldier_id
                        && \Str::contains($_SERVER['HTTP_REFERER'], 'my-soldiers-shifts')
                        && current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier']))
                    )
                    ->hiddenOn('view'),
                DateTimePicker::make('start_date')
                    ->label(__('Start date'))
                    ->required(),
                DateTimePicker::make('end_date')
                    ->label(__('End date'))
                    ->required(),
            ]),

        ];
    }

    public static function afterSave($shift, $record)
    {
        $soldier = Soldier::find($shift['soldier_id']);
        $shift = Shift::find($record->id);
        $soldier->update(['capacity_hold' => (float) $soldier->capacity_hold + $shift->task->parallel_weight]);
    }

    protected static function getOptions(string $department_name): array
    {
        $options = [
            'reserves' => __('Reserves'),
            'department' => '"' . $department_name . '" ' . __('Department'),
            'all' => __('All'),
        ];
        if (current(array: array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) != 'manager') {
            return collect($options)->put('my_soldiers', __('My Soldiers'))->toArray();
        }
        return $options;
    }

    public static function getTitle(): string|Htmlable
    {
        return __('Shift');
    }
}