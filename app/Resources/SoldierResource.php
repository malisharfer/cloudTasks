<?php

namespace App\Resources;

use App\Filters\NumberFilter;
use App\Forms\Components\Flatpickr;
use App\Models\Department;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ReplicateAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
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
                Section::make()->schema([SoldierResource::personalDetails()])->columns(),
                Section::make()->schema(SoldierResource::soldierDetails())->columns(),
                Section::make()->schema(SoldierResource::reserveDays())->columns()->visible(fn (Get $get) => $get('is_reservist')),
                Section::make()->schema(SoldierResource::constraints())->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                TextColumn::make('user.first_name')
                    ->label('Name')
                    ->formatStateUsing(function ($record) {
                        return $record->user->last_name.' '.$record->user->first_name;
                    })
                    ->searchable(['user->first_name', 'user->last_name'])
                    ->sortable(),
                BadgeColumn::make('gender')
                    ->formatStateUsing(fn ($state) => $state ? 'Male' : 'Female')
                    ->badge()
                    ->color(fn ($state) => $state ? 'primary' : 'info')
                    ->sortable(),
                BooleanColumn::make('is_reservist'),
                TextColumn::make('reserve_dates')->listWithLineBreaks()->limitList(1)->expandableLimitedList()->placeholder('---')->toggleable(),
                TextColumn::make('next_reserve_dates')->listWithLineBreaks()->limitList(1)->expandableLimitedList()->placeholder('---')->toggleable(),
                TextColumn::make('enlist_date')->sortable()->date()->toggleable(),
                TextColumn::make('course')->toggleable(isToggledHiddenByDefault: true),
                BooleanColumn::make('has_exemption')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_shift')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_night')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_weekend')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('capacity')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('capacity_hold')->numeric()->toggleable(),
                BooleanColumn::make('is_trainee')->toggleable(isToggledHiddenByDefault: true),
                BooleanColumn::make('is_mabat')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('qualifications')
                    ->placeholder('no qualifications')->toggleable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (request()->input('team_id')) {
                    return $query->where('team_id', request()->input('team_id'));
                }
            })
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Toggle'),
            )
            ->filters([
                NumberFilter::make('course'),
                NumberFilter::make('max_shift'),
                NumberFilter::make('max_night'),
                NumberFilter::make('max_weekend'),
                NumberFilter::make('capacity'),
                NumberFilter::make('capacity_hold'),
                SelectFilter::make('gender')
                    ->options([
                        true => 'Male',
                        false => 'Female',
                    ])
                    ->default(null),
                SelectFilter::make('qualifications')
                    ->multiple()
                    ->searchable()
                    ->options(Task::all()->pluck('name', 'name'))
                    ->query(function (Builder $query, array $data) {
                        return collect($data['values'])->map(fn ($qualification) => $query->whereJsonContains('qualifications', $qualification));
                    })
                    ->default(null),
                Filter::make('reservist')
                    ->query(fn (Builder $query): Builder => $query->where('is_reservist', 1))
                    ->toggle(),
                Filter::make('is_mabat')
                    ->query(fn (Builder $query): Builder => $query->where('is_mabat', true))
                    ->toggle(),
                Filter::make('has_exemption')
                    ->query(fn (Builder $query): Builder => $query->where('has_exemption', true))
                    ->toggle(),
                Filter::make('is_trainee')
                    ->query(fn (Builder $query): Builder => $query->where('is_trainee', true))
                    ->toggle(),
                Filter::make('enlist_date')
                    ->form([
                        Fieldset::make('Enlist date')
                            ->schema([
                                DatePicker::make('recruitment_from'),
                                DatePicker::make('recruitment_until'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['recruitment_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enlist_date', '>=', $date),
                            )
                            ->when(
                                $data['recruitment_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('enlist_date', '<=', $date),
                            );
                    }),

            ], layout: \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->deferFilters()
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter')
            )
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('update reserve days')
                        ->icon('heroicon-o-pencil')
                        ->color('primary')
                        ->form(function ($record) {
                            return [
                                Flatpickr::make('next_reserve_dates')
                                    ->multiple()
                                    ->default($record->next_reserve_dates)
                                    ->minDate(now()->addMonth()->startOfMonth())
                                    ->maxDate(now()->addMonth()->endOfMonth())
                                    ->required(),
                            ];
                        })
                        ->action(function (Soldier $record, array $data): void {
                            $record->next_reserve_dates = $data['next_reserve_dates'];
                            $record->save();
                        })
                        ->closeModalByClickingAway(false)
                        ->hidden(fn ($record) => ! $record->is_reservist),
                    ReplicateAction::make()
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->after(function (Soldier $replica): void {
                            redirect()->route('filament.app.resources.soldiers.edit', ['record' => $replica->id]);
                        })
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

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->hasRole('manager')) {
            return parent::getEloquentQuery()->where('id', '!=', User::where('userable_id', auth()->user()->id)->value('userable_id'));
        }

        return parent::getEloquentQuery()->where('team_id', Team::select('id')->where('commander_id', auth()->user()->userable_id))
            ->orWhere('team_id', Team::select('id')->where('department_id', Department::select('id')->where('commander_id', auth()->user()->userable_id)));
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
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->length(7)
                    ->required(),
            ])->columns(3);
    }

    public static function soldierDetails(): array
    {
        return [
            ToggleButtons::make('gender')
                ->options([
                    1 => 'Male',
                    0 => 'Female',
                ])
                ->grouped()
                ->required(),
            DatePicker::make('enlist_date')
                ->seconds(false),
            TextInput::make('course')
                ->numeric()
                ->minValue(0),
            Select::make('capacity')
                ->placeholder('select an option')
                ->options(fn (): array => collect(range(0, 12))->mapWithKeys(fn ($number) => [(string) ($number / 4) => (string) ($number / 4)])->toArray())
                ->required(),
            Hidden::make('capacity_hold')
                ->default(0),
            Section::make([
                Toggle::make('is_reservist')
                    ->live(),
                Toggle::make('is_permanent'),
                Toggle::make('has_exemption'),
            ])->columns(3),
        ];
    }

    public static function reserveDays(): array
    {
        return [
            Flatpickr::make('reserve_dates')
                ->multiple()
                ->minDate(today())
                ->maxDate(today()->endOfMonth())
                ->columnSpan('full'),
        ];
    }

    public static function constraints(): array
    {
        return [
            Section::make([
                TextInput::make('max_shift')->numeric()->minValue(0),
                TextInput::make('max_night')->numeric()->minValue(0)->maxValue(31),
                TextInput::make('max_weekend')->default('')->numeric()->minValue(0)->maxValue(5),
            ])
                ->columns(3),
            Split::make([
                Section::make([
                    Toggle::make('is_trainee'),
                    Toggle::make('is_mabat'),
                ])
                    ->columns(2),
                Select::make('qualifications')
                    ->multiple()
                    ->placeholder('select an option')
                    ->options(Task::all()->pluck('name', 'name')), ])->columns(2)
                ->columnSpan('full'),
        ];

    }
}
