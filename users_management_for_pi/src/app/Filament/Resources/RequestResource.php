<?php

namespace App\Filament\Resources;

use App\Enums\Options;
use App\Enums\Requests\AuthenticationType;
use App\Enums\Requests\ServiceType;
use App\Enums\Requests\Status;
use App\Filament\Filters\DateRangeFilter;
use App\Filament\Resources\RequestResource\Pages;
use App\Models\Request;
use App\Rules\Identity;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequestResource extends Resource
{
    protected static ?string $model = Request::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

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
                DatePicker::make('expiration_date')
                    ->label(__('expiration date'))
                    ->minDate(now())
                    ->hint(__('Default for service type regular: 1 year, for the rest: 6 months')),
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
        } else {
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
                DateRangeFilter::make('created_at'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('approval')
                        ->label(__('approval'))
                        ->action(fn (Request $record) => self::updateStatusAction($record, Status::Approved))
                        ->icon('heroicon-o-check-circle')
                        ->visible(auth()->user()->role === 'Admin')
                        ->disabled(fn (Request $record) => $record->status === Status::Approved || $record->status === Status::Denied)
                        ->color('success'),
                    Tables\Actions\Action::make('deny')
                        ->label(__('deny'))
                        ->action(fn (Request $record) => self::updateStatusAction($record, Status::Denied))
                        ->icon('heroicon-o-x-circle')
                        ->visible(auth()->user()->role === 'Admin')
                        ->disabled(fn (Request $record) => $record->status === Status::Approved || $record->status === Status::Denied)
                        ->color('danger'),
                    Tables\Actions\DeleteAction::make(),
                ])->tooltip(__('Actions')),
            ])
            ->query(function (Request $query) {
                if (auth()->user()->role === 'User') {
                    return $query
                        ->where('submit_username', auth()->user()->name);
                }

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
                ->badge(),
        ];
    }

    public static function updateStatusAction(Request $record, Status $status)
    {
        $response = $record->updateStatus($status);
        $notification = Notification::make();
        $response ? $notification->title(__('Successfully updated:)')) : $notification->title(__('Unsuccessfully updated:('));
        $response ? $notification->body(__('The change has been sent to ') . $record->submit_username) :
            $notification->body(__('The system encountered difficulties when adding the user:' . $record->submit_username . 'please try again.'));
        $response ? $notification->success() : $notification->danger();
        $notification->send();
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