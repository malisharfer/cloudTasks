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
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Actions as NotificationsService;
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
                            fn () => Cache::remember('users', 30 * 60, fn () => User::all())->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName])
                        )
                        ->live()
                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                            if (! empty($get('members')) && collect($get('members'))->contains($state)) {
                                $set('members', collect($get('members'))->filter(fn ($member) => $member !== $state));
                            }
                        })
                        ->optionsLimit(Soldier::count())
                        ->placeholder(__('Select commander'))
                        ->searchable()
                        ->getSearchResultsUsing(fn ($search) => User::whereRaw("first_name || ' ' || last_name LIKE ?", "%{$search}%")
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName])
                            ->toArray())
                        ->required(),
                    Select::make('department_id')
                        ->label(__('Department'))
                        ->relationship('department')
                        ->options(Department::select('id', 'name')->distinct()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->getSearchResultsUsing(fn ($search) => Department::where('name', 'like', "%{$search}%")
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray())
                        ->default(request()->input('department_id'))
                        ->required(),
                    Select::make('members')
                        ->label(__('Members'))
                        ->options(
                            fn (Get $get) => Cache::remember('users', 30 * 60, fn () => User::all())
                                ->filter(function ($user) use ($get): bool {
                                    return $user->userable_id !== (int) $get('commander_id');
                                })
                                ->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName])
                        )
                        ->formatStateUsing(fn (?Team $team, string $operation) => $operation === 'edit' ?
                            collect($team->members)->map(fn (Soldier $soldier) => $soldier->id)->toArray() :
                            null)
                        ->live()
                        ->optionsLimit(Soldier::count())
                        ->placeholder(__('Add a team member'))
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(fn ($search) => User::whereRaw("first_name || ' ' || last_name LIKE ?", "%{$search}%")
                            ->get()
                            ->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName])
                            ->toArray()),
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
                    ->formatStateUsing(fn ($state) => $state->last_name.' '.$state->first_name)
                    ->label(__('Commander'))
                    ->searchable(
                        query: function ($query, $search) {
                            $query->whereHas('commander', function ($query) use ($search) {
                                $query->whereHas('user', function ($query) use ($search) {
                                    $query->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%");
                                });
                            });
                        }
                    )
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
                                    fn (Team $record) => Cache::remember('users', 30 * 60, fn () => User::all())
                                        ->filter(function ($user) use ($record) {
                                            return $record->commander_id !== $user->userable_id;
                                        })
                                        ->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName])
                                )
                                ->formatStateUsing(fn (Team $record) => collect($record->members)->map(fn (Soldier $soldier) => $soldier->id))
                                ->optionsLimit(Soldier::count())
                                ->multiple()
                                ->searchable(),
                        ])
                        ->closeModalByClickingAway(false)
                        ->action(function (array $data, Team $record): void {
                            $memberIds = collect($record->members)->pluck('id') ?? collect([]);
                            $newMembers = $data['members'] ?? collect();
                            Soldier::whereIn('id', $memberIds)
                                ->whereNotIn('id', $newMembers)
                                ->update(['team_id' => null]);

                            Soldier::whereIn('id', $newMembers)
                                ->update(['team_id' => $record->id]);
                        }),
                    Action::make('View members')
                        ->label(__('View members'))
                        ->color('success')
                        ->icon('heroicon-o-user-group')
                        ->badge(fn ($record) => collect($record->members)->count())
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
            ->body(__('The commander you selected is already registered as a commander. His selection will leave his :type without a commander. Are you sure?'))
            ->actions([
                NotificationsService\Action::make('view')
                    ->label(__('View ').($teams->isNotEmpty() ? __('Team') : __('Department')))
                    ->button()
                    ->url(
                        fn () => $teams->isNotEmpty() ?
                        route('filament.app.resources.teams.index', ['commander_id' => $data['commander_id']]) :
                        route('filament.app.resources.departments.index', ['commander_id' => $data['commander_id']])
                    ),
                NotificationsService\Action::make(__('Confirm'))
                    ->button()
                    ->dispatch('confirmCreate', ['teams' => $teams, 'departments' => $departments]),
                NotificationsService\Action::make(__('Cancel'))
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
        if (auth()->user()->hasRole('manager') || auth()->user()->hasRole('shifts-assignment')) {
            return parent::getEloquentQuery();
        }
        if (auth()->user()->hasRole('team-commander')) {
            return parent::getEloquentQuery()->where('commander_id', auth()->user()->userable_id);
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
