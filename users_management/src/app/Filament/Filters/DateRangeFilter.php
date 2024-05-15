<?php

namespace App\Filament\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Tables\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DateRangeFilter extends BaseFilter
{
    protected string $column;

    public function withIndicator(): self
    {
        $this->indicateUsing(function (array $data): ?string {
            $indicators = '';
            $data['from'] ? $indicators .= __('created from').' '.Carbon::parse($data['from'])->format('d-m-Y').' ' : '';
            $data['until'] ? $indicators .= __('created until').' '.Carbon::parse($data['until'])->format('d-m-Y').' ' : '';

            return $indicators;
        });

        return $this;
    }

    public function getFormSchema(): array
    {
        return [
            Group::make([
                DatePicker::make('from')->label(__('created from')),
                DatePicker::make('until')->label(__('created until')),
            ]),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->useColumn($this->getName());
        $this->withIndicator();
    }

    public function useColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    public function apply(Builder $query, array $data = []): Builder
    {
        if ($data['from'] || $data['until']) {
            return $query
                ->when(
                    $data['from'],
                    fn (Builder $query, $date): Builder => $query->whereDate($this->column, '>=', $date),
                )
                ->when(
                    $data['until'],
                    fn (Builder $query, $date): Builder => $query->whereDate($this->column, '<=', $date),
                );
        }

        return $query;
    }
}
