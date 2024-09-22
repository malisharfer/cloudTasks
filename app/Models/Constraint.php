<?php

namespace App\Models;

use App\Enums\ConstraintType;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
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
            Select::make('constraint_type')
                ->required()
                ->label('Constraint Name')
                ->reactive()
                ->live()
                ->options(fn (Get $get) => self::availableOptions($get))
                ->afterStateUpdated(fn (callable $set, $state, Get $get) => self::updateDates($set, $state, $get)),
            Hidden::make('start_date')->required(),
            Hidden::make('end_date')->required(),
            Grid::make()
                ->visible(fn ($get) => in_array($get('constraint_type'), ['Medical', 'Vacation', 'School', 'Not task', 'Low priority not task']))
                ->schema([
                    DateTimePicker::make('start_date')->required(),
                    DateTimePicker::make('end_date')->required(),
                ]),
        ];
    }

    private static function updateDates(callable $set, $state, Get $get): void
    {
        $dateRange = self::getDateForConstraint($state, $get);
        $set('start_date', $dateRange['start_date']);
        $set('end_date', $dateRange['end_date']);
    }

    private static function availableOptions($get): array
    {
        $start_date = Carbon::parse($get('start_date'));
        $options = array_combine(array_map(fn ($enum) => $enum->value, ConstraintType::cases()), array_map(fn ($enum) => $enum->value, ConstraintType::cases()));

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

        return array_filter($options, fn ($option) => ($limits[$option] ?? 0) === 0 || ($usedCounts[$option] ?? 0) < ($limits[$option] ?? 0));
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

    private static function getDateForConstraint($constraintType, $get)
    {
        $startDate = Carbon::parse($get('start_date'));
        $endDate = Carbon::parse($get('end_date'));

        return match ($constraintType) {
            'Not evening', 'Not Thursday evening' => [
                'start_date' => $startDate->setTimeFromTimeString('18:00:00'),
                'end_date' => $endDate->setTimeFromTimeString('23:59:00'),
            ],
            'Not weekend', 'Low priority not weekend' => [
                'start_date' => $startDate->startOfWeek(Carbon::THURSDAY),
                'end_date' => $endDate->next(modifier: Carbon::SUNDAY)->startOfDay(),
            ],
            'Medical', 'Vacation', 'School', 'Not task', 'Low priority not task' => [
                'start_date' => $get('start_date'),
                'end_date' => $get('end_date'),
            ],
        };
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
        return $this->constraint_type.' - '.$this->soldier_name;
    }

    public function getConstraintColorAttribute()
    {
        return ConstraintType::from($this->constraint_type)->getColor();
    }
}
