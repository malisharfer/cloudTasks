<?php

namespace App\Resources;

use App\Models\Task;
use App\Resources\TaskResource\Pages;
use App\Resources\TaskResource\Pages\CreateTask;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-m-squares-plus';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make(CreateTask::getSteps()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    Tables\Columns\TextColumn::make('name')
                        ->description('Name', position: 'above')
                        ->size(TextColumn\TextColumnSize::Large),
                    Tables\Columns\TextColumn::make('type')
                        ->description('Type', position: 'above')
                        ->size(TextColumn\TextColumnSize::Large),
                    Tables\Columns\TextColumn::make('parallel_weight')
                        ->description('Parallel Weight', position: 'above')
                        ->size(TextColumn\TextColumnSize::Large),
                    Tables\Columns\ColorColumn::make('color')
                        ->copyable()
                        ->copyMessage('Color code copied'),
                ]),
                Panel::make([
                    Stack::make([
                        Tables\Columns\TextColumn::make('start_hour')
                            ->description('Start at', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('duration')
                            ->description('Duration', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('is_alert')
                            ->description('Alert', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large)
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        Tables\Columns\TextColumn::make('department_name')
                            ->description('Department', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                    ])
                        ->space(2)
                        ->extraAttributes(['style' => 'display: flex; flex-direction: row; flex-wrap: wrap; justify-content: space-between; align-items: center;']),
                    Stack::make([
                        Tables\Columns\TextColumn::make('recurrence.type')
                            ->description('Recurrence type', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('recurrence.days_in_week')
                            ->description('Days in week', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('recurrence.dates_in_month')
                            ->description('Dates in month', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('recurrence.start_date')
                            ->description('StartDate', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('recurrence.end_date')
                            ->description('EndDate', position: 'above')
                            ->size(TextColumn\TextColumnSize::Large),
                    ])
                        ->space(2)
                        ->extraAttributes(['style' => 'display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; align-items: center; border: 1px solid #e7e7e7;']),
                ])->collapsible(),

            ])
            ->filters([
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
        ];
    }
}
