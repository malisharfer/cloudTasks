<?php

namespace App\Resources;

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource\Pages;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getModelLabel(): string
    {
        return __('Team');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Teams');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required(),
                    Select::make('commander_id')
                        ->label(__('Commander'))
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
                        ->label(__('Department'))
                        ->relationship('department')
                        ->options(Department::all()->pluck('name', 'id'))
                        ->searchable()
                        ->default(request()->input('department_id'))
                        ->required(),
                    Select::make('members')
                        ->label(__('Members'))
                        ->options(
                            fn () => Cache::remember('users', 30 * 60, function () {
                                return User::all();
                            })->mapWithKeys(function ($user) {
                                return [$user->userable_id => $user->displayName];
                            })
                        )
                        ->placeholder(__('Add a team member'))
                        ->multiple()
                        ->searchable(),
                ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('commander.user')
                    ->label(__('Commander'))
                    ->formatStateUsing(function ($state) {
                        return $state->last_name.', '.$state->first_name;
                    })
                    ->label(__('Commander'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(__('Department'))
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
            ->actions([
                ActionGroup::make([
                    Action::make('members')
                        ->label(__('Add member'))
                        ->color('primary')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Select::make('members')
                                ->label(__('Members'))
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
                        ->label(__('View members'))
                        ->color('success')
                        ->icon('heroicon-o-user-group')
                        ->badge(fn ($record) => Soldier::where('team_id', $record->id)->count())
                        ->url(fn (Team $record): string => route('filament.app.resources.soldiers.index', ['team_id' => $record->id])),
                    EditAction::make(),
                    DeleteAction::make()
                        ->label(__('Delete'))
                        ->modalHeading(__('Delete').' '.self::getModelLabel()),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function checkCommander($teams, $departments, $data)
    {
        Notification::make()
            ->title(__('Save team'))
            ->persistent()
            ->body(__('The commander you selected is already registered as a commander. His selection will leave his soldiers without a commander. Are you sure?'))
            ->actions([
                \Filament\Notifications\Actions\Action::make(__('View ').($teams->isNotEmpty() ? __('Team') : __('Department')))
                    ->button()
                    ->url(
                        fn () => $teams->isNotEmpty() ?
                        route('filament.app.resources.teams.index', ['commander_id' => $data['commander_id']]) :
                        route('filament.app.resources.departments.index', ['commander_id' => $data['commander_id']])
                    ),
                \Filament\Notifications\Actions\Action::make(__('Confirm'))
                    ->button()
                    ->dispatch('confirmCreate', data: ['teams' => $teams, 'departments' => $departments]),
                \Filament\Notifications\Actions\Action::make(__('Cancel'))
                    ->button()
                    ->close(),
            ])
            ->send();
    }

    public static function confirm(array $teams, array $departments, $commander_id)
    {

        if (collect($teams)->isNotEmpty()) {
            self::unAssignTeamCommander($commander_id);
        }
        if (collect($departments)->isNotEmpty()) {
            self::unAssignDepartmentCommander($commander_id);
        }
    }

    protected static function unAssignTeamCommander($commander_id)
    {
        Team::where('commander_id', $commander_id)
            ->update(['commander_id' => null]);
    }

    protected static function unAssignDepartmentCommander($commander_id): void
    {
        Department::where('commander_id', $commander_id)
            ->update(['commander_id' => null]);

        $user = User::where('userable_id', $commander_id)->first();
        $user->removeRole('department-commander');
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
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
