<?php

namespace App\Resources;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Resources\DepartmentResource\Pages;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function getModelLabel(): string
    {
        return __('Department');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Departments');
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
                        ->relationship('commander', 'id')
                        ->options(
                            fn () => Cache::remember('users', 30 * 60, function () {
                                return User::all();
                            })->mapWithKeys(function ($user) {
                                return [$user->userable_id => $user->displayName];
                            })
                        )
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
                    ->formatStateUsing(function ($state) {
                        return $state->last_name.' '.$state->first_name;
                    })
                    ->label(__(key: 'Commander'))
                    ->searchable()
                    ->sortable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                if (request()->input('commander_id')) {
                    return $query->where('commander_id', request()->input('commander_id'));
                }
                if (request()->input('department_id')) {
                    return $query->where('id', request()->input('department_id'));
                }
            })
            ->actions([
                ActionGroup::make([
                    Action::make('teams')
                        ->label(__('Add team'))
                        ->color('primary')
                        ->icon('heroicon-o-user-plus')
                        ->url(fn (Department $record): string => route('filament.app.resources.teams.create', ['department_id' => $record->id])),
                    Action::make('View teams')
                        ->label(__('View teams'))
                        ->color('success')
                        ->icon('heroicon-o-user-circle')
                        ->badge(fn ($record) => Team::where('department_id', $record->id)->count())
                        ->url(fn (Department $record): string => route('filament.app.resources.teams.index', ['department_id' => $record->id])),
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
        $isCommanderNull = $data['commander_id'] === null;
        $type = $teams->isNotEmpty() ? 'soldiers' : 'teams';

        $body = $isCommanderNull
            ? __('You did not choose a commander. With your approval, you leave the team without a commander. Are you sure?', ['type' => $type])
            : __('The commander you selected is already registered as a commander. His selection will leave his :type without a commander. Are you sure?', ['type' => $type]);
        $actions = array_filter([
            ! $isCommanderNull ? NotificationsService\Action::make($teams->isNotEmpty() ? __('View team') : __('View department'))
                ->button()
                ->url(
                    fn () => $teams->isNotEmpty()
                    ? route('filament.app.resources.teams.index', ['commander_id' => $data['commander_id']])
                    : route('filament.app.resources.departments.index', ['commander_id' => $data['commander_id']])
                ) : null,
            NotificationsService\Action::make('confirm')
                ->label(__('Confirm'))
                ->button()
                ->dispatch('confirmCreate', data: ['teams' => $teams, 'departments' => $departments]),
            NotificationsService\Action::make(__('Cancel'))
                ->button()
                ->close(),
        ]);
        Notification::make()
            ->title(__('Save department'))
            ->persistent()
            ->body($body)
            ->actions($actions)
            ->send();
    }

    public static function confirm(array $teams, array $departments, $commander_id): void
    {
        if (collect($teams)->isNotEmpty()) {
            self::unAssignTeamCommander($commander_id);
        }
        if (collect($departments)->isNotEmpty()) {
            self::unAssignDepartmentCommander($commander_id);
        }
    }

    protected static function unAssignTeamCommander($commander_id): void
    {
        Team::where('commander_id', $commander_id)
            ->update(['commander_id' => null]);
        $user = User::where('userable_id', $commander_id)->first();
        $user->removeRole('team-commander');
    }

    protected static function unAssignDepartmentCommander($commander_id): void
    {
        Department::where('commander_id', $commander_id)
            ->update(['commander_id' => null]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
