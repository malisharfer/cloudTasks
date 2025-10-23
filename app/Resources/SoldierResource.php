<?php

namespace App\Resources;

use App\Enums\ConstraintType;
use App\Filters\NumberFilter;
use App\Forms\Components\Flatpickr;
use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SoldierResource extends Resource
{
    protected static ?string $model = Soldier::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([self::personalDetails()])->columns(),
                Section::make()->schema(self::soldierDetails())->columns(),
                Section::make()->schema(self::constraints())->columns(),
                Section::make()->schema(self::constraintsLimit())->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.displayName')
                    ->label(__('Full name'))
                    ->formatStateUsing(fn ($record) => $record->user->last_name.' '.$record->user->first_name)
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('user', function ($query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(['first_name', 'last_name']),
                TextColumn::make('type')
                    ->formatStateUsing(
                        fn ($record) => $record->type == 'collection' ? __('Collection') : __('Survival')
                    )
                    ->label(__('Type')),
                BooleanColumn::make('is_reservist')
                    ->label(__('Reservist')),
                BadgeColumn::make('gender')
                    ->label(__('Gender'))
                    ->formatStateUsing(fn ($state) => $state ? __('Male') : __('Female'))
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'primary')
                    ->sortable(),
                TextColumn::make('role')
                    ->label(__('Role'))
                    ->default(
                        function ($record) {
                            if ($record->user) {
                                $roles = $record->user->getRoleNames();
                                $roles->count() > 1 ? $roles->shift(1) : null;
                                $roles->all();

                                return array_map(fn ($role) => match ($role) {
                                    'manager' => __('Manager'),
                                    'shifts-assignment' => __('A shifts assignment'),
                                    'department-commander' => __('Department commander'),
                                    'team-commander' => __('Team commander'),
                                    'soldier' => __('Soldier'),
                                    default => __('No roles'),
                                }, $roles->toArray());
                            }
                        }
                    ),
                TextColumn::make('teamSoldier')
                    ->label(__('Team'))
                    ->visible(collect(auth()->user()->getRoleNames())->intersect(['manager', 'shifts-assignment', 'department-commander'])->isNotEmpty())
                    ->placeholder(__('Not associated'))
                    ->default(function ($record) {
                        $soldier = Soldier::find($record->id);

                        return $soldier->team ? $soldier->team->name : null;
                    }),
                TextColumn::make('enlist_date')
                    ->label(__('Enlist date'))
                    ->sortable()
                    ->date(),
                TextColumn::make('capacity_hold')
                ->default(function ($record) {
                        $userId = $record->id;
                        $startOfMonth = now()->startOfMonth();
                        $endOfMonth = now()->endOfMonth();
                        $total = 0;
                        Shift::where('soldier_id', $userId)
                            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                                $query
                                    ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                                    ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth]);
                            })
                            ->with(['task' => fn($q) => $q->withTrashed()])
                            ->chunk(100, function ($shifts) use (&$total) {
                                foreach ($shifts as $shift) {
                                    $total += $shift->parallel_weight ?? $shift->task?->parallel_weight ?? 0;
                                }
                            });
                        return $total;
                    })
                    ->label(__('Capacity hold'))
                    ->numeric(),
                TextColumn::make('qualifications')
                    ->label(__('Qualifications'))
                    ->placeholder(__('No qualifications')),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (request()->input('team_id')) {
                    return $query->where('team_id', request()->input('team_id'));
                }
            })
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label(__('Columns')),
            )
            ->filters([
                NumberFilter::make('course')->label(__('Course')),
                NumberFilter::make('max_shifts')->label(__('Max shifts')),
                NumberFilter::make('max_nights')->label(__('Max nights')),
                NumberFilter::make('max_weekends')
                    ->isFloat(true)
                    ->label(__('Max weekends')),
                NumberFilter::make('max_alerts')->label(__('Max alerts')),
                NumberFilter::make('max_in_parallel')->label(__('Max in parallel')),
                NumberFilter::make('capacity')
                    ->isFloat(true)
                    ->label(__('Capacity')),
                SelectFilter::make('gender')
                    ->label(__('Gender'))
                    ->options([
                        true => __('Male'),
                        false => __('Female'),
                    ])
                    ->default(null),
                SelectFilter::make('qualifications')
                    ->label(__('Qualifications'))
                    ->multiple()
                    ->searchable()
                    ->options(Task::withTrashed()->select('type')
                        ->distinct()
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all())
                    ->query(
                        fn (Builder $query, array $data) => collect($data['values'])->map(function ($qualification) use ($query) {
                            return $query->whereJsonContains('qualifications', $qualification);
                        })
                    )
                    ->default(null),
                Filter::make('reservist')
                    ->label(__('Reservist'))
                    ->query(fn (Builder $query): Builder => $query->where('is_reservist', 1))
                    ->toggle(),
                Filter::make('is_mabat')
                    ->label(__('Is mabat'))
                    ->query(fn (Builder $query): Builder => $query->where('is_mabat', true))
                    ->toggle(),
                Filter::make('has_exemption')
                    ->label(__('Exemption'))
                    ->query(fn (Builder $query): Builder => $query->where('has_exemption', true))
                    ->toggle(),
                Filter::make('is_trainee')
                    ->label(__('Is trainee'))
                    ->query(fn (Builder $query): Builder => $query->where('is_trainee', true))
                    ->toggle(),
                SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options([
                        'survival' => __('Survival'),
                        'collection' => __('Collection'),
                    ])
                    ->default(null),
                Filter::make('enlist_date')
                    ->form([
                        Fieldset::make('Enlist date')
                            ->label(__('Enlist date'))
                            ->schema([
                                DatePicker::make('recruitment_from')
                                    ->label(__('From')),
                                DatePicker::make('recruitment_until')
                                    ->label(__('To'))
                                    ->after('recruitment_from'),
                            ]),
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $query
                            ->when(
                                $data['recruitment_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enlist_date', '>=', $date),
                            )
                            ->when(
                                $data['recruitment_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enlist_date', '<=', $date),
                            )
                    ),

            ], FiltersLayout::Modal)
            ->defaultSort('user.displayName')
            ->filtersFormColumns(4)
            ->deferFilters()
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label(__('Filter'))
            )
            ->recordAction(ViewAction::class)
            ->recordUrl(null)
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make()
                        ->label(__('Delete'))
                        ->modalHeading(__('Delete').' '.self::getModelLabel()),
                    ViewAction::make()
                        ->label(__('Display'))
                        ->mutateRecordDataUsing(function (array $data, $record): array {
                            $data['shifts_assignment'] = in_array('shifts-assignment', User::where('userable_id', $record->id)->first()->getRoleNames()->toArray());

                            return $data;
                        }),
                    Action::make('update reserve days')
                        ->label(__('Update reserve days'))
                        ->icon('heroicon-o-pencil')
                        ->color('primary')
                        ->form(
                            fn ($record) => [
                                Flatpickr::make('last_reserve_dates')
                                    ->label(__('Last reserve dates'))
                                    ->multiple()
                                    ->default($record->last_reserve_dates)
                                    ->minDate($record->enlist_date)
                                    ->maxDate(now()->subMonth()->endOfMonth()),
                                Flatpickr::make('reserve_dates')
                                    ->label(__('Reserve dates'))
                                    ->multiple()
                                    ->default($record->reserve_dates)
                                    ->minDate(now()->startOfMonth())
                                    ->maxDate(now()->endOfMonth()),
                                Flatpickr::make('next_reserve_dates')
                                    ->label(__('Next reserve dates'))
                                    ->multiple()
                                    ->default($record->next_reserve_dates)
                                    ->minDate(now()->addMonth()->startOfMonth())
                                    ->maxDate(now()->addMonth()->endOfMonth()),
                            ]
                        )
                        ->action(function (Soldier $record, array $data): void {
                            $record->last_reserve_dates = $data['last_reserve_dates'];
                            $record->reserve_dates = $data['reserve_dates'];
                            $record->next_reserve_dates = $data['next_reserve_dates'];
                            $record->save();
                        })
                        ->closeModalByClickingAway(false)
                        ->hidden(fn ($record) => ! $record->is_reservist),
                    ReplicateAction::make()
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->after(fn (Soldier $replica, $record) => self::replicateSoldier($replica, $record))
                        ->successNotification(null)
                        ->closeModalByClickingAway(false),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function replicateSoldier(Soldier $replica, $record)
    {
        $user = new User;
        $user->first_name = '';
        $user->last_name = '';
        $user->password = '';
        $user->userable_type = "App\Models\Soldier";
        $user->userable_id = $replica->id;
        $user->save();
        $user->assignRole('soldier');
        in_array('shifts-assignment', $record->user->getRoleNames()->toArray()) ? $user->assignRole('shifts-assignment') : null;
        $replica['last_reserve_dates'] = [];
        $replica['reserve_dates'] = [];
        $replica['next_reserve_dates'] = [];
        $replica->save();
        session()->put('shifts_assignment', User::where('userable_id', $record->id)->first()->getRoleNames()->contains('shifts-assignment'));
        redirect()->route('filament.app.resources.soldiers.edit', ['record' => $replica->id]);
    }

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->hasRole('manager') || auth()->user()->hasRole('shifts-assignment')) {
            return parent::getEloquentQuery()->where('id', '!=', auth()->user()->userable_id);
        }

        return parent::getEloquentQuery()
            ->when(auth()->user()->hasRole('department-commander'), function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('team_id', Department::whereHas('commander', function ($query) {
                        $query->where('id', auth()->user()->userable_id);
                    })->first()?->teams->pluck('id') ?? collect([]))
                        ->orWhereIn('id', Department::whereHas('commander', function ($query) {
                            $query->where('id', auth()->user()->userable_id);
                        })->first()?->teams->pluck('commander_id') ?? collect([]));
                });
            })
            ->when(auth()->user()->hasRole('team-commander'), function ($query) {
                $query->where('team_id', Team::whereHas('commander', function ($query) {
                    $query->where('id', auth()->user()->userable_id);
                })->value('id') ?? collect([]));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoldiers::route('/'),
            'create' => Pages\CreateSoldier::route('/create'),
            'edit' => Pages\EditSoldier::route('/{record}/edit'),
        ];
    }

    public static function personalDetails(): Fieldset
    {
        return Fieldset::make('')
            ->relationship('user')
            ->schema([
                TextInput::make('first_name')
                    ->label(__('First name'))
                    ->required(),
                TextInput::make('last_name')
                    ->label(__('Last name'))
                    ->required(),
                TextInput::make('password')
                    ->label(__('Personal number'))
                    ->password()
                    ->revealable()
                    ->length(7)
                    ->hiddenOn('view')
                    ->required(),
            ])->columns(3);
    }

    public static function soldierDetails(): array
    {
        return [
            Select::make('type')
                ->label(__('Type'))
                ->options([
                    'survival' => __('Survival'),
                    'collection' => __('Collection'),
                ])
                ->placeholder(__('Select soldier type'))
                ->required(),
            ToggleButtons::make('gender')
                ->options([
                    1 => __('Male'),
                    0 => __('Female'),
                ])
                ->default(1)
                ->label(__('Gender'))
                ->grouped()
                ->required(),
            Toggle::make('shifts_assignment')
                ->label(__('A shifts assignment'))
                ->visible(auth()->user()->getRoleNames()->contains('manager')),
            DatePicker::make('enlist_date')
                ->label(__('Enlist date'))
                ->seconds(false),
            TextInput::make('course')
                ->label(__('Course'))
                ->numeric()
                ->minValue(0)
                ->required(),
            TextInput::make('capacity')
                ->numeric()
                ->step(0.25)
                ->minValue(0)
                ->label(__('Capacity'))
                ->required(),
            Section::make([
                Toggle::make('is_reservist')
                    ->label(__('Reservist')),
                Toggle::make('is_permanent')
                    ->label(__('Is permanent')),
                Toggle::make('has_exemption')
                    ->label(__('Exemption')),
            ])->columns(3),
        ];
    }

    public static function constraints(): array
    {
        return [
            Section::make([
                TextInput::make('max_shifts')
                    ->label(__('Max shifts'))
                    ->numeric()
                    ->step(1)
                    ->minValue(0)
                    ->required()
                    ->default(0),
                TextInput::make('max_nights')
                    ->label(__('Max nights'))
                    ->numeric()
                    ->step(1)
                    ->minValue(0)
                    ->required()
                    ->lte('max_shifts')
                    ->validationMessages([
                        'lte' => __('The field cannot be greater than max_shifts field'),
                    ])
                    ->default(0),
                TextInput::make('max_weekends')
                    ->label(__('Max weekends'))
                    ->numeric()
                    ->step(0.25)
                    ->minValue(0)
                    ->required()
                    ->lte('capacity')
                    ->validationMessages([
                        'lte' => __('The field cannot be greater than capacity field'),
                    ])
                    ->default(0),
                TextInput::make('max_alerts')
                    ->label(__('Max alerts'))
                    ->numeric()
                    ->step(1)
                    ->minValue(0)
                    ->required()
                    ->default(0),
                TextInput::make('max_in_parallel')
                    ->label(__('Max in parallel'))
                    ->numeric()
                    ->step(1)
                    ->minValue(0)
                    ->required()
                    ->default(0),
            ])
                ->columns(5),
            Section::make([
                Toggle::make('is_trainee')
                    ->label(__('Is trainee')),
                Toggle::make('is_mabat')
                    ->label(__('Is mabat')),
                Select::make('qualifications')
                    ->label(__('Qualifications'))
                    ->multiple()
                    ->placeholder(__('Select qualifications'))
                    ->options(Task::select('type')
                        ->distinct()
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all()),
            ])->columns(3),
        ];
    }

    public static function constraintsLimit()
    {
        return [
            Fieldset::make('constraints')
                ->label(__('Constraints limit'))
                ->schema([
                    Section::make([
                        Group::make([
                            Toggle::make('not_thursday_evening')
                                ->label(__('Not Thursday evening constraint')),
                            Toggle::make('not_sunday_morning')
                                ->label(__('Not Sunday morning constraint')),
                        ])->columns(2),
                    ]),
                    Section::make([
                        Group::make([
                            TextInput::make('Not weekend')
                                ->label(__('Not weekend'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Not weekend']),
                            TextInput::make('Low priority not weekend')
                                ->label(__('Low priority not weekend'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Low priority not weekend']),
                            TextInput::make('Not task')
                                ->label(__('Not task'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Not task']),
                            TextInput::make('Low priority not task')
                                ->label(__('Low priority not task'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Low priority not task']),
                            TextInput::make('Not evening')
                                ->label(__('Not evening'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Not evening']),
                            TextInput::make('Not Thursday evening')
                                ->label(__('Not Thursday evening'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Not Thursday evening']),
                            TextInput::make('Not Sunday morning')
                                ->label(__('Not Sunday morning'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Not Sunday morning']),
                            TextInput::make('Vacation')
                                ->label(__('Vacation'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Vacation']),
                            TextInput::make('Medical')
                                ->label(__('Medical'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['Medical']),
                            TextInput::make('School')
                                ->label(__('School'))
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->default(ConstraintType::getLimit()['School']),
                        ])
                            ->statePath('constraints_limit')
                            ->columns(10)
                            ->columnSpan(3)
                            ->label('Constraints'),
                    ]),
                ]),

        ];
    }

    public static function getModelLabel(): string
    {
        return __('Soldier');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Soldiers');
    }
}
