<?php

namespace App\Models;

use App\Enums\ConstraintType;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Constraint extends Model
{
    use HasFactory;

    protected $fillable = [
        'constraint_type',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d',
        'end_date' => 'datetime:Y-m-d',
    ];

    public function soldiers(): BelongsTo
    {
        return $this->belongsTo(Soldier::class);
    }

    public static function getSchema(): array
    {
        return [
            Placeholder::make('')
                ->content(content: fn (Constraint $constraint) => $constraint->soldier_name)
                ->inlineLabel(),
            ToggleButtons::make('constraint_type')
                ->required()
                ->label(__('Constraint Name'))
                ->reactive()
                ->live()
                ->inline()
                ->options(fn (Get $get) => self::availableOptions($get))
                ->afterStateUpdated(fn (callable $set, $state, Get $get) => self::updateDates($set, $state, $get)),
            Hidden::make('start_date')->required(),
            Hidden::make('end_date')->required(),
            Grid::make()
                ->visible(fn ($get) => in_array($get('constraint_type'), ['Medical', 'Vacation', 'School', 'Not task', 'Low priority not task']))
                ->schema([
                    DateTimePicker::make('start_date')->label(__('Start date'))->required(),
                    DateTimePicker::make('end_date')->label(__('End date'))->required(),
                ]),
        ];
    }

    private static function availableOptions($get): array
    {
        $start_date = Carbon::parse($get('start_date'));
        $options = array_combine(
            array_map(fn ($enum) => $enum->value, ConstraintType::cases()),
            array_map(fn ($enum) => $enum->getLabel(), ConstraintType::cases())
        );

        if ($start_date->isFriday() || $start_date->isSaturday()) {
            unset($options[ConstraintType::NOT_THURSDAY_EVENING->value]);
        } elseif ($start_date->isThursday()) {
        } else {
            unset($options[ConstraintType::NOT_THURSDAY_EVENING->value]);
            unset($options[ConstraintType::NOT_WEEKEND->value]);
            unset($options[ConstraintType::LOW_PRIORITY_NOT_WEEKEND->value]);
        }
        $usedCounts = self::getUsedCountsForCurrentMonth($get('start_date'), $get('end_date'));
        $limits = ConstraintType::getLimit();

        return array_filter($options, fn ($option) => ($limits[array_search($option, array_map(fn ($enum) => $enum->getLabel(), ConstraintType::cases()))] ?? 0) === 0
            || ($usedCounts[array_search($option, array_map(fn ($enum) => $enum->getLabel(), ConstraintType::cases()))] ?? 0) < ($limits[array_search($option, array_map(fn ($enum) => $enum->getLabel(), ConstraintType::cases()))] ?? 0));
    }

    private static function getUsedCountsForCurrentMonth($startDate, $endDate): array
    {
        $currentUserId = auth()->user()->userable_id;

        foreach (ConstraintType::cases() as $enum) {
            $usedCount = DB::table('constraints')
                ->where('soldier_id', $currentUserId)
                ->where('constraint_type', $enum->value)
                ->whereBetween('start_date', [
                    Carbon::parse($startDate)->startOfMonth(),
                    Carbon::parse($endDate)->endOfMonth(),
                ])
                ->count();
            $usedCounts[$enum->value] = $usedCount;
        }

        return $usedCounts;
    }

    public static function updateDates(callable $set, $state, Get $get)
    {
        $constraintType = $get('constraint_type');
        $startDate = Carbon::parse($get('start_date'));
        $endDate = Carbon::parse($get('end_date'));

        switch ($constraintType) {
            case 'Medical':
            case 'Vacation':
            case 'School':
            case 'Not task':
            case 'Low priority not task':
                $set('start_date', $startDate->setTime(0, 0, 0)->toDateTimeString());
                $set('end_date', $endDate->setTime(23, 59, 0)->toDateTimeString());
                break;

            case 'Not evening':
            case 'Not Thursday evening':
                $set('start_date', $startDate->setTime(18, 0, 0)->toDateTimeString());
                $set('end_date', $endDate->setTime(23, 59, 0)->toDateTimeString());
                break;

            case 'Not weekend':
            case 'Low priority not weekend':
                $set('start_date', $startDate->startOfWeek(Carbon::THURSDAY)->toDateTimeString());
                $set('end_date', $endDate->next(Carbon::SUNDAY)->startOfDay()->toDateTimeString());
                break;

            default:
                $set('start_date', $startDate->setTime(18, 0, 0)->toDateTimeString());
                $set('end_date', $endDate->setTime(23, 59, 0)->toDateTimeString());
                break;
        }
    }

    protected static function booted()
    {
        static::creating(function ($constraint) {
            $constraint->soldier_id = $constraint->soldier_id ?: ($constraint->getCurrentUserSoldier() ? $constraint->getCurrentUserSoldier()->id : null);
        });
    }

    private function getCurrentUserSoldier(): ?Soldier
    {
        $user = auth()->user();
        if ($user && $user->userable instanceof Soldier) {
            return $user->userable;
        }

        return null;
    }

    public function getSoldierNameAttribute()
    {
        $user_name = User::where('userable_id', $this->soldier_id)->get(['first_name', 'last_name']);

        return $user_name->first()?->first_name.' '.$user_name->first()?->last_name;
    }

    public function getConstraintNameAttribute()
    {
        $translatedConstraint = __($this->constraint_type);

        return $translatedConstraint.' - '.$this->soldier_name;
    }

    public function getConstraintColorAttribute()
    {
        return ConstraintType::from($this->constraint_type)->getColor();
    }

    public static function getTitle(): string|Htmlable
    {
        return __('Constraint');
    }
}
