<?php

namespace App\Models;

use App\Enums\ConstraintType;
use App\Traits\CommanderSoldier;
use App\Traits\EventsByRole;
use Cache;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Constraint extends Model
{
    use EventsByRole;
    use HasFactory;

    protected $fillable = [
        'constraint_type',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'constraint_type' => ConstraintType::class,
        'start_date' => 'datetime:Y-m-d H:i:s',
        'end_date' => 'datetime:Y-m-d H:i:s',
    ];

    public function soldier(): BelongsTo
    {
        return $this->belongsTo(Soldier::class);
    }

    public static function getSchema(): array
    {
        return [
            Placeholder::make('')
                ->content(fn (Constraint $constraint) => $constraint->soldier_name)
                ->inlineLabel()
                ->hidden(fn (Constraint $constraint) => $constraint->soldier_id === auth()->user()->userable_id),
            Select::make('soldier_id')
                ->label(__('Soldier'))
                ->hiddenOn('view')
                ->visible(fn () => auth()->user()->getRoleNames()->count() > 1
                    && \Str::contains($_SERVER['HTTP_REFERER'], 'my-soldiers-constraint')
                )
                ->options(fn () => CommanderSoldier::getCommanderSoldier())
                ->afterStateUpdated(fn ($state) => session()->put('soldier_id', $state))
                ->required(),
            ToggleButtons::make('constraint_type')
                ->required()
                ->label(__('Constraint Name'))
                ->reactive()
                ->live()
                ->hiddenOn('view')
                ->inline()
                ->options(fn (Get $get) => self::availableOptions($get('start_date'), $get('end_date')))
                ->afterStateUpdated(fn (callable $set, $state, Get $get) => self::updateDates($set, $state, $get)),
            ToggleButtons::make('constraint_type_view')
                ->label(__('Constraint Name'))
                ->inline()
                ->visibleOn('view')
                ->options(fn (Constraint $constraint) => [
                    $constraint->constraint_type->getLabel(),
                ]),
            Hidden::make('start_date')
                ->label(__('Start date'))
                ->required(),
            Hidden::make('end_date')
                ->label(__('End date'))
                ->required(),
            Placeholder::make('')
                ->content(__('The constraint will only be approved after approval by the commander'))
                ->visible(fn () => auth()->user()->getRoleNames()->count() === 1)
                ->hiddenOn('view')
                ->extraAttributes(['style' => 'color: red; font-family: Arial, Helvetica, sans-serif; font-size: 20px']),
            Grid::make()
                ->schema([
                    DateTimePicker::make('start_date')
                        ->label(__('Start date'))
                        ->minDate(today())
                        ->required(),
                    DateTimePicker::make('end_date')
                        ->label(__('End date'))
                        ->after('start_date')
                        ->required(),
                ]),
        ];
    }

    public static function requestConstraint($data)
    {
        $commander = Soldier::find(auth()->user()->userable_id)->team->commander->user;
        Notification::make()
            ->title(__('Do you approve the constraint request'))
            ->body(
                __('Shift details', [
                    'name' => Soldier::find(auth()->user()->userable_id)->user->displayName,
                    'startDate' => $data['start_date'],
                    'endDate' => $data['end_date'],
                    'type' => __($data['constraint_type']),
                ])
            )
            ->actions(
                [
                    NotificationAction::make(__('Confirm'))
                        ->button()
                        ->icon('heroicon-s-hand-thumb-up')
                        ->color('success')
                        ->dispatch('confirmConstraint', [
                            'user' => auth()->user()->id,
                            'constraintName' => $data['constraint_type'],
                            'startDate' => $data['start_date'],
                            'endDate' => $data['end_date'],
                        ])
                        ->close(),
                    NotificationAction::make(__('Deny'))
                        ->button()
                        ->icon('heroicon-m-hand-thumb-down')
                        ->color('danger')
                        ->dispatch('denyConstraint', [
                            'user' => auth()->user()->id,
                            'constraintName' => __($data['constraint_type']),
                            'startDate' => $data['start_date'],
                            'endDate' => $data['end_date'],
                        ])
                        ->close(),
                ]
            )
            ->sendToDatabase($commander, true);
    }

    public static function requestEditConstraint($data)
    {
        $commander = Soldier::find(auth()->user()->userable_id)->team->commander->user;
        Notification::make()
            ->title(__('Do you approve the request to edit the constraint?'))
            ->body(
                $data['oldConstraint']['constraint_type']->value === $data['newConstraint']['constraint_type'] ?
                __('Constraint edit times', [
                    'commanderName' => $commander->displayName,
                    'soldierName' => Soldier::find(auth()->user()->userable_id)->user->displayName,
                    'constraintName' => __($data['oldConstraint']['constraint_type']->value),
                    'startDate' => $data['oldConstraint']['start_date']->format('Y-m-d H:i:s'),
                    'endDate' => $data['oldConstraint']['end_date']->format('Y-m-d H:i:s'),
                    'toStartDate' => $data['newConstraint']['start_date'],
                    'toEndDate' => $data['newConstraint']['end_date'],
                ]) :
                __('Constraint edit type', [
                    'commanderName' => $commander->displayName,
                    'soldierName' => Soldier::find(auth()->user()->userable_id)->user->displayName,
                    'constraintName' => __($data['oldConstraint']['constraint_type']->value),
                    'startDate' => $data['oldConstraint']['start_date']->format('Y-m-d H:i:s'),
                    'endDate' => $data['oldConstraint']['end_date']->format('Y-m-d H:i:s'),
                    'toConstraintName' => __($data['newConstraint']['constraint_type']),
                    'toStartDate' => $data['newConstraint']['start_date'],
                    'toEndDate' => $data['newConstraint']['end_date'],
                ])
            )
            ->actions(
                [
                    NotificationAction::make(__('Confirm'))
                        ->button()
                        ->icon('heroicon-s-hand-thumb-up')
                        ->color('success')
                        ->dispatch('confirmConstraintEdit', [
                            'user' => auth()->user()->id,
                            'data' => $data,
                        ])
                        ->close(),
                    NotificationAction::make(__('Deny'))
                        ->button()
                        ->icon('heroicon-m-hand-thumb-down')
                        ->color('danger')
                        ->dispatch('denyConstraintEdit', [
                            'user' => auth()->user()->id,
                            'data' => $data,
                        ])
                        ->close(),
                ]
            )
            ->sendToDatabase($commander, true);
    }

    public static function getAvailableOptions($startDate, $endDate, $withLimit = true): array
    {
        return static::availableOptions($startDate, $endDate, $withLimit);
    }

    private static function availableOptions($startDate, $endDate, $withLimit = true): array
    {
        $start_date = Carbon::parse($startDate);
        $end_date = Carbon::parse($endDate);
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
        if (! $start_date->isSunday()) {
            unset($options[ConstraintType::NOT_SUNDAY_MORNING->value]);
        }
        if (! $end_date->isSameDay($start_date)) {
            unset($options[ConstraintType::NOT_SUNDAY_MORNING->value]);
            unset($options[ConstraintType::NOT_THURSDAY_EVENING->value]);
            unset($options[ConstraintType::NOT_EVENING->value]);
        }
        if ($withLimit && auth()->user()->getRoleNames()->count() == 1) {
            $usedCounts = self::getUsedCountsForCurrentMonth($startDate, $endDate);
            $limits = Soldier::where('id', auth()->user()->userable_id)->pluck('constraints_limit')->first() ?: ConstraintType::getLimit();
            $constraintsWithinLimit = [];
            $queryConstraints = Constraint::where('soldier_id', auth()->user()->userable_id)
                ->whereBetween('start_date', [$startDate, $endDate])
                ->pluck('constraint_type')
                ->toArray();

            foreach ($options as $constraint => $label) {
                $used = $usedCounts[$constraint] ?? 0;
                $limit = $limits[$constraint] ?? 0;
                if ($limit === 0 || $used < $limit) {
                    if (! in_array($constraint, $queryConstraints)) {
                        $constraintsWithinLimit[$constraint] = $label;
                    }
                }
            }

            return $constraintsWithinLimit;
        }

        return $options;
    }

    private static function getUsedCountsForCurrentMonth($startDate, $endDate): array
    {
        foreach (ConstraintType::cases() as $enum) {
            $usedCount = Constraint::where('soldier_id', auth()->user()->userable_id)
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
        $endDate = max($startDate, Carbon::parse($get('end_date')));

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
                $set('end_date', $startDate->endOfWeek(Carbon::SATURDAY)->setTime(23, 59, 0)->toDateTimeString());
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
            $constraint->soldier_id = $constraint->soldier_id ?: ($constraint->getCurrentUserSoldier() ?: null);
            session()->put('soldier_id', null);
        });

        static::updating(function ($constraint) {
            $constraint->soldier_id = $constraint->soldier_id ?: ($constraint->getCurrentUserSoldier() ?: null);
            session()->put('soldier_id', null);
        });
    }

    private function getCurrentUserSoldier()
    {
        if (session()->get('soldier_id')) {
            return session()->get('soldier_id');
        }
        $user = auth()->user();
        if ($user && $user->userable instanceof Soldier) {
            return $user->userable_id;
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
        return $this->soldier_id == auth()->user()->userable_id
            ? $this->constraint_type->getLabel()
            : $this->constraint_type->getLabel().' '.$this->soldier_name;
    }

    public function getConstraintColorAttribute()
    {
        return $this->constraint_type->getColor();
    }

    public static function getFilters($calendar)
    {
        return Action::make('Filters')
            ->iconButton()
            ->label(__('Filter'))
            ->icon('heroicon-o-funnel')
            ->form(fn () => [
                Select::make('soldier_id')
                    ->label(__('Soldier'))
                    ->options(fn (): array => Cache::remember('users', 30 * 60, fn () => User::all())->mapWithKeys(fn ($user) => [$user->userable_id => $user->displayName])
                        ->toArray())
                    ->multiple(),
                Select::make('constraint_type')
                    ->label(__('Constraint Name'))
                    ->options(fn () => collect(ConstraintType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()]))
                    ->multiple(),
            ])
            ->modalSubmitActionLabel(__('Filter'))
            ->modalCancelAction(false)
            ->action(function (array $data) use ($calendar) {
                if (! empty($data['soldier_id']) || ! empty($data['constraint_type'])) {
                    $calendar->filterData = $data;
                    $calendar->filter = true;
                } else {
                    $calendar->filter = false;
                    $calendar->filterData = [];
                }
                $calendar->refreshRecords();
            });
    }

    public static function filter($fetchInfo, $filterData)
    {
        $query = self::getEventsByRole(Constraint::with('soldier'));

        return $query
            ->where(function ($query) use ($fetchInfo) {
                $query->where('start_date', '>=', Carbon::create($fetchInfo['start'])->setTimezone('Asia/Jerusalem'))
                    ->where('end_date', '<=', Carbon::create($fetchInfo['end'])->setTimezone('Asia/Jerusalem'));
            })
            ->when(! empty($filterData['soldier_id']), function ($query) use ($filterData) {
                $query->whereIn('soldier_id', $filterData['soldier_id']);
            })
            ->when(! empty($filterData['constraint_type']), function ($query) use ($filterData) {
                $query->whereIn('constraint_type', collect($filterData['constraint_type']));
            })
            ->get();
    }

    public static function activeFilters($calendar)
    {
        if (! $calendar->filter) {
            return [];
        }
        $data = $calendar->filterData;
        $labels = collect([]);
        if (! empty($data['soldier_id'])) {
            $soldiers = collect($data['soldier_id'])
                ->map(fn ($id) => Soldier::find($id)->user->displayName)
                ->implode(', ');
            $labels->push(__('Soldiers').': '.$soldiers);
        }
        if (! empty($data['constraint_type'])) {
            $types = collect($data['constraint_type'])
                ->map(fn ($type) => ConstraintType::from($type)->getLabel())
                ->implode(', ');
            $labels->push(__('Constraint Name').': '.$types);
        }

        return $labels->toArray();
    }

    public static function getTitle(): string
    {
        return __('Constraint');
    }
}
