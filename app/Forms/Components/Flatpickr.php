<?php

namespace App\Forms\Components;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;
use Filament\Forms\Components\Concerns;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasAffixes;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Contracts\CanBeLengthConstrained;
use Filament\Forms\Components\Contracts\HasAffixActions;
use Filament\Forms\Components\Field;

class Flatpickr extends Field implements CanBeLengthConstrained, HasAffixActions
{
    use CanBeReadOnly,Concerns\CanBeLengthConstrained,HasAffixes,HasExtraInputAttributes;

    protected string $view = 'forms.components.flatpickr';

    protected bool $rangePicker = false;

    protected ?string $mode = 'multiple';

    protected bool $multiplePicker = false;

    protected array $config = [];

    protected ?string $dateFormat = 'Y-m-d';

    protected ?string $conjunction = ', ';

    protected Carbon|string|null|Closure $maxDate = null;

    protected Carbon|string|null|Closure $minDate = null;

    public function getConfig(): array
    {
        return [
            'dateFormat' => $this->dateFormat,
            'conjunction' => $this->conjunction,
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefixIcon('heroicon-o-calendar-days');

        if (! $this->dehydrateStateUsing) {
            $this->dehydrateStateUsing(static function (Flatpickr $component, $state) {
                return self::dehydratePickerState($component, $state);
            });
        }
    }

    public static function dehydratePickerState($component, $state)
    {
        if (blank($state)) {
            return null;
        }
        if (! $state instanceof CarbonInterface) {
            if ($component->isMultiplePicker()) {
                $stateAsString = is_array($state) ? implode(', ', $state) : $state;
                $range = \Str::of($stateAsString)->explode($component->getConjunction());
                $state = collect($range)->map(fn ($date) => Carbon::parse($date)
                    ->setTimezone(config('app.timezone'))->format($component->getDateFormat()))
                    ->toArray();
            }
        }

        return $state;
    }

    public function default(mixed $state): static
    {
        $this->defaultState = $state;
        $this->hasDefaultState = true;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getConjunction(): ?string
    {
        return $this->conjunction;
    }

    public function conjunction(?string $conjunction): static
    {
        $this->conjunction = $conjunction;

        return $this;
    }

    public function getMaxDate(): Carbon|string|null
    {
        return $this->maxDate;
    }

    public function maxDate(Carbon|string|null|Closure $maxDate = 'now'): static
    {
        $this->maxDate = $maxDate ? Carbon::parse($maxDate) : $maxDate;

        return $this;
    }

    public function getMinDate(): Carbon|string|null
    {
        return $this->minDate;
    }

    public function minDate(Carbon|string|null|Closure $minDate): static
    {
        $this->minDate = $minDate ? Carbon::parse($minDate) : $minDate;

        return $this;
    }

    public function range(bool $rangePicker = true): static
    {
        $this->rangePicker = $rangePicker;

        return $this;
    }

    public function isRangePicker(): bool
    {
        return $this->rangePicker;
    }

    public function multiple(bool $multiplePicker = true): static
    {
        $this->multiplePicker = $multiplePicker;

        return $this;
    }

    public function isMultiplePicker(): bool
    {
        return $this->multiplePicker;
    }

    public function dateFormat(string $dateFormat): static
    {
        $this->dateFormat = $dateFormat;

        return $this;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function getThemeAsset(): string
    {
        return asset('css/coolsam/flatpickr/flatpickr-them.css');
    }
}
