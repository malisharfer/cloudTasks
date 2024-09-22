<?php

namespace App\Resources;

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource\Pages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                Select::make('commander_id')
                    ->relationship('commander')
                    ->options(
                        fn () => Cache::remember('users', 30 * 60, function () {
                            return User::all();
                        })->mapWithKeys(function ($user) {
                            return [$user->userable_id => $user->displayName];
                        })
                    )
                    ->searchable()
                    ->required(),
                Select::make('department_id')
                    ->relationship('department')
                    ->options(Department::all()->pluck('name', 'id'))
                    ->searchable()
                    ->default(request()->input('department_id'))
                    ->required(),
                Select::make('members')
                    ->options(
                        fn () => Cache::remember('users', 30 * 60, function () {
                            return User::all();
                        })->mapWithKeys(function ($user) {
                            return [$user->userable_id => $user->displayName];
                        })
                    )
                    ->multiple()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('commander.user')
                    ->formatStateUsing(function ($state) {
                        return $state->last_name.', '.$state->first_name;
                    })
                    ->label('Commander')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->url(fn (Team $record): string => route('filament.app.resources.departments.index', ['department_id' => $record->department_id]))
                    ->searchable()
                    ->sortable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (request()->input('commander_id')) {
                    return $query->where('commander_id', request()->input('commander_id'));
                }
                if (request()->input('department_id')) {
                    return $query->where('department_id', request()->input('department_id'));
                }
            })
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('members')
                        ->label('Add member')
                        ->color('primary')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Select::make('members')
                                ->options(
                                    fn (Team $record) => Cache::remember('users', 30 * 60, function () {
                                        return User::all();
                                    })
                                        ->filter(function ($user) use ($record) {
                                            $soldier_team_id = Soldier::where('id', $user->userable_id)->pluck('team_id');

                                            return $soldier_team_id->first() !== $record->id;
                                        })
                                        ->mapWithKeys(function ($user) {
                                            return [$user->userable_id => $user->displayName];
                                        })
                                )
                                ->multiple()
                                ->searchable(),
                        ])
                        ->action(function (array $data, Team $record): void {
                            collect($data['members'])->map(fn ($soldier_id) => Soldier::where('id', $soldier_id)
                                ->update(['team_id' => $record->id]));
                        }),
                    Action::make('View members')
                        ->color('success')
                        ->icon('heroicon-o-user-group')
                        ->badge(fn ($record) => Soldier::where('team_id', $record->id)->count())
                        ->url(fn (Team $record): string => route('filament.app.resources.soldiers.index', ['team_id' => $record->id])),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        if (auth()->user()->hasRole('manager')) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->where('department_id', Department::select('id')->where('commander_id', auth()->user()->userable_id));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
        ];
    }
}
