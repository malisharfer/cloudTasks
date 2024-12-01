<?php

namespace App\Filters;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

class NumberFilter extends Filter
{
    protected function setUp(): void
    {
        parent::setup();

        $this->form([
            Fieldset::make(__($this->getlabel()))
                ->schema([
                    Select::make('range_condition')
                        ->hiddenLabel()
                        ->placeholder(__('Select condition'))
                        ->live()
                        ->options([
                            'equal' => __('Equal'),
                            'not_equal' => __('Not equal'),
                            'between' => __('Between'),
                            'greater_than' => __('Greater than'),
                            'greater_than_equal' => __('Greater than equal'),
                            'less_than' => __('Less than'),
                            'less_than_equal' => __('Less than equal'),
                        ])
                        ->afterStateUpdated(function (callable $set) {
                            $set('range_equal', null);
                            $set('range_not_equal', null);
                            $set('range_between_from', null);
                            $set('range_between_to', null);
                            $set('range_greater_than', null);
                            $set('range_greater_than_equal', null);
                            $set('range_less_than', null);
                            $set('range_less_than_equal', null);
                        }),
                    TextInput::make('range_equal')
                        ->label(__('Equal'))
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'equal'),
                    TextInput::make('range_not_equal')
                        ->label(__('Not equal'))
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'not_equal'),
                    Grid::make([
                        'default' => 1,
                        'sm' => 2,
                    ])
                        ->schema([
                            TextInput::make('range_between_from')
                                ->label(__('Between from'))
                                ->numeric(),
                            TextInput::make('range_between_to')
                                ->label(__('Between to'))
                                ->numeric(),
                        ])
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'between'),
                    TextInput::make('range_greater_than')
                        ->label(__('Greater than'))
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'greater_than'),
                    TextInput::make('range_greater_than_equal')
                        ->label(__('Greater than equal'))
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'greater_than_equal'),
                    TextInput::make('range_less_than')
                        ->label(__('Less than'))
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'less_than'),
                    TextInput::make('range_less_than_equal')
                        ->label(__('Less than equal'))
                        ->hiddenLabel()
                        ->numeric()
                        ->visible(fn (Get $get): bool => $get('range_condition') === 'less_than_equal'),
                ])
                ->columns(1),
        ])
            ->query(function (Builder $query, array $data) {
                $allObjects = $query->getModel()::all();

                return $query
                    ->when(
                        ! empty($data['range_equal']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] == $data['range_equal'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    )
                    ->when(
                        ! empty($data['range_not_equal']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] != $data['range_not_equal'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    )
                    ->when(
                        ! empty($data['range_between_from']) && ! empty($data['range_between_to']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] >= $data['range_between_from'] && $object[$this->getName()] <= $data['range_between_to'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    )
                    ->when(
                        ! empty($data['range_greater_than']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] > $data['range_greater_than'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    )
                    ->when(
                        ! empty($data['range_greater_than_equal']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] >= $data['range_greater_than_equal'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    )
                    ->when(
                        ! empty($data['range_less_than']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] < $data['range_less_than'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    )
                    ->when(
                        ! empty($data['range_less_than_equal']),
                        function () use ($allObjects, $data, $query) {
                            $filteredIds = $allObjects
                                ->filter(fn ($object) => $object[$this->getName()] <= $data['range_less_than_equal'])
                                ->pluck('id');

                            return $query->whereIn('id', $filteredIds);
                        }
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if (! empty($data['range_between_from']) || ! empty($data['range_between_to'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is between').' :fromValue '.__('And').' :toValue', [
                        'label' => $this->getLabel(),
                        'fromValue' => $data['range_between_from'],
                        'toValue' => $data['range_between_to'],
                    ]))
                        ->removeField('range_between_from')
                        ->removeField('range_between_to');
                }

                if (! empty($data['range_equal'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is equal').' to :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_equal'],
                    ]))
                        ->removeField('range_equal');
                }

                if (! empty($data['range_not_equal'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is not equal').' to :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_not_equal'],
                    ]))
                        ->removeField('range_not_equal');
                }

                if (! empty($data['range_greater_than'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is greater than').' :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_greater_than'],
                    ]))
                        ->removeField('range_greater_than');
                }

                if (! empty($data['range_greater_than_equal'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is greater than or equal').' :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_greater_than_equal'],
                    ]))
                        ->removeField('range_greater_than_equal');
                }

                if (! empty($data['range_less_than'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is less than').' :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_less_than'],
                    ]))
                        ->removeField('range_less_than');
                }

                if (! empty($data['range_less_than_equal'])) {
                    $indicators[] = Indicator::make(__(':label '.__('Is less than or equal').' :value', [
                        'label' => $this->getLabel(),
                        'value' => $data['range_less_than_equal'],
                    ]))
                        ->removeField('range_less_than_equal');
                }

                return $indicators;
            });
    }
}
