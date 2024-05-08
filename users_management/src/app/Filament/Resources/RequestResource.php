<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Request;
use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;
use App\Enums\Requests\Status;
use App\Enums\Requests\ServiceType;
use App\Enums\Requests\AuthenticationType;
use App\Rules\Identity;
use App\Enums\Options;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Filters\Filter;
use App\Services\GetUsers;
use Closure;
use App\Enums\Users\Role;



class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('requests');
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('identity')
                    ->label(__('identity'))
                    ->unique(ignoreRecord: true)
                    ->rules([new Identity()])
                    ->required()
                    ->maxLength(9)
                    ->minLength(7),
                TextInput::make('first_name')
                    ->label(__('first name (in english)'))
                    ->regex("/^[A-Za-z][A-Za-z ,.'-]+$/")
                    ->required()
                    ->minLength(2)
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label(__('last name (in english)'))
                    ->regex("/^[A-Za-z][A-Za-z ,.'-]+$/")
                    ->required()
                    ->minLength(2)
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('phone'))
                    ->tel()
                    ->maxLength(10)
                    ->required(),
                TextInput::make('email')
                    ->label(__('email'))
                    ->email()
                    ->maxLength(255),
                TextInput::make('unit')
                    ->label(__('unit'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('sub')
                    ->label(__('project'))
                    ->required()
                    ->maxLength(255),
                Select::make('authentication_type')
                    ->label(__('authentication type'))
                    ->options(Options::getOptions(AuthenticationType::cases()))
                    ->required(),
                Select::make('service_type')
                    ->label(__('service type'))
                    ->options(Options::getOptions(ServiceType::cases()))
                    ->required(),
                TextInput::make('validity')
                    ->label(__('validity required'))
                    ->required()
                    ->numeric()
                    ->default(365)
                    ->maxLength(5),  
                Textarea::make('description')
                    ->label(__('description'))
                    ->required()
                    ->maxLength(1000)
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $viewType = request()->input('viewType', 'Table');

        if ($viewType === 'Card') {
            $table = $table->contentGrid(['md' => 2, 'xl' => 3])->columns(
                array_merge(self::commonColumns(), [Split::make([])])
            );
        }
        else{
            $table = $table->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes())
            ->striped()
            ->columns(self::commonColumns());
        }
        return $table   
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('request status'))
                    ->multiple()
                    ->options(Options::getOptions(Status::cases())),
                DateRangeFilter::make('created_at')
                    ->label(__('created at'))
                    ->setAutoApplyOption(true),
            ])
            ->actions([
                    ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\Action::make(__('approval'))
                            ->action(fn (Request $record) => self::updateStatusAction($record, Status::Approved))
                            ->icon('heroicon-o-check-circle')
                            ->visible(auth()->user()->role === Role::Admin)
                            ->disabled(fn (Request $record) => $record->status === Status::Approved)
                            ->color('success'),
                        Tables\Actions\Action::make(__('deny'))
                            ->action(fn (Request $record) => self::updateStatusAction($record, Status::Denied))
                            ->icon('heroicon-o-x-circle')
                            ->visible(auth()->user()->role === Role::Admin)
                            ->disabled(fn (Request $record) => $record->status === Status::Denied)
                            ->color('danger'),
                        Tables\Actions\DeleteAction::make(),
                    ])->tooltip(__('Actions'))
            ])
            ->query(function (Request $query) {
                $user = auth()->user();
                $query = $user->role === Role::User ? $query->where('submit_username', $user->name): $query;
                return $query;
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function commonColumns(): array
    {
        return [
                TextColumn::make('index')
                    ->label('')
                    ->rowIndex(),
                TextColumn::make('created_at')
                    ->label(__('created at'))
                    ->dateTime('d-m-Y')
                    ->sortable(),
                TextColumn::make('submit_username')
                    ->label(__('created by'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('identity')
                    ->label(__('identity'))
                    ->searchable(),
                TextColumn::make('fullname')
                    ->label(__('full name'))
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->whereRaw("first_name || ' ' || last_name LIKE ?", ["%$search%"]);
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('last_name', $direction)
                            ->orderBy('first_name', $direction);
                    }),
                TextColumn::make('phone')
                    ->label(__('phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('email'))                    
                    ->copyable()
                    ->copyMessage(__('Email address copied'))
                    ->copyMessageDuration(1500)
                    ->placeholder(__('No email')),
                TextColumn::make('status')
                    ->label(__('request status'))
                    ->formatStateUsing(fn (Status $state): string => __($state->value))
                    ->sortable()
                    ->badge()
            ];
    }

    public static function updateStatusAction(Request $record, Status $status) {
        $record->updateStatus($status);
        Notification::make()
            ->title(__('Successfully updated'))
            ->body(__('The change has been sent to ') . $record->submit_username)
            ->success()
            ->send();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
        ];
    }
}
