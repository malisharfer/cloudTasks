<?php

namespace App\Resources;

use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Resources\ProfileResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProfileResource extends Resource
{
    protected static ?string $model = Soldier::class;

    protected static bool $shouldRegisterNavigation = false;

    public static ?string $label = 'My profile';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make([])
                    ->schema([
                        Fieldset::make(__('Personal Information'))
                            ->relationship('user')
                            ->schema([
                                TextInput::make('first_name')->label(__('First name'))->required(),
                                TextInput::make('last_name')->label(__('Last name'))->required(),
                                DatePicker::make('enlist_date')
                                    ->label(__('Enlist date'))
                                    ->seconds(false),
                            ]),
                        Section::make([
                            Select::make('qualifications')
                                ->label(__('Qualifications'))
                                ->placeholder(__('Select qualifications'))
                                ->options(Task::select('type')
                                    ->distinct()
                                    ->orderBy('type')
                                    ->pluck('type', 'type')
                                    ->all()),
                            TextInput::make('capacity')
                                ->numeric()
                                ->step(0.25)
                                ->minValue(0)
                                ->label(__('Capacity'))
                                ->required(),
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
                        ])->columns(2)->visible(fn () => auth()->user()->getRoleNames()->count() > 1),
                        Section::make([
                            Toggle::make('is_permanent')->Label(__('Is permanent')),
                            Toggle::make('has_exemption')->label(__('Exemption')),
                            Toggle::make('is_trainee')->label(__('Is trainee')),
                            Toggle::make('is_mabat')->label(__('Is mabat')),
                        ])->columns(4),

                    ]),

            ]);

    }

    public static function table(Table $table): Table
    {

        return $table
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->columns([
                split::make([
                    Stack::make([
                        TextColumn::make('user.first_name')
                            ->label(__('Full name'))
                            ->formatStateUsing(fn ($record) => $record->user->last_name.' '.$record->user->first_name
                            )->weight(FontWeight::SemiBold)->description(__('Full name'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('enlist_date')->weight(FontWeight::SemiBold)->description(__('Enlist date'), 'above')->size(TextColumnSize::Large)->date(),
                        TextColumn::make('course')->weight(FontWeight::SemiBold)->description(__('Course'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('max_shifts')->weight(FontWeight::SemiBold)->description(__('Max shifts'), 'above')->size(TextColumnSize::Large),
                    ]),
                    Stack::make([

                        TextColumn::make('max_nights')->weight(FontWeight::SemiBold)->description(__('Max nights'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('max_weekends')->weight(FontWeight::SemiBold)->description(__('Max weekends'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('max_alerts')->weight(FontWeight::SemiBold)->description(__('Max alerts'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('max_in_parallel')->weight(FontWeight::SemiBold)->description(__('Max in parallel'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('capacity')->weight(FontWeight::SemiBold)->description(__('Capacity'), 'above')->size(TextColumnSize::Large),
                        TextColumn::make('capacity_hold')
                            ->default(function () {
                                $now = now();

                                return Shift::where('soldier_id', auth()->user()->userable_id)
                                    ->where(function ($query) use ($now) {
                                        $query->whereYear('start_date', $now->year)
                                            ->whereMonth('start_date', $now->month)
                                            ->orWhere(function ($query) use ($now) {
                                                $query->whereYear('end_date', $now->year)
                                                    ->whereMonth('end_date', $now->month);
                                            });
                                    })
                                    ->with(['task' => function ($query) {
                                        $query->withTrashed();
                                    }])
                                    ->get()
                                    ->sum(fn (Shift $shift) => $shift->parallel_weight ?? $shift->task->parallel_weight);
                            })
                            ->weight(FontWeight::SemiBold)
                            ->description(__('Capacity hold'), 'above')
                            ->size(TextColumnSize::Large),
                        TextColumn::make('qualifications')->weight(FontWeight::SemiBold)->description(__('Qualifications'), 'above')->size(TextColumnSize::Large),
                    ]),
                ]),
            ])
            ->searchable(false)
            ->paginated(false)
            ->filters([
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Soldier::query()->where('id', auth()->user()->userable->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfiles::route('/'),
            'edit' => Pages\EditProfile::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function getPluralModelLabel(): string
    {
        return __('My profile');
    }
}
