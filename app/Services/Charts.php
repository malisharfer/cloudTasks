<?php

namespace App\Services;

use App\Enums\TaskKind;
use App\Models\Shift;
use App\Models\Soldier;
use Carbon\Carbon;

class Charts
{
    protected $course;

    protected $date;

    protected $kind;

    public function __construct($course, $year, $month, $kind)
    {
        $this->course = $course;
        $this->kind = $kind;
        $this->date = Carbon::create($year, $month, 1);
    }

    public function getData()
    {
        $data = collect([]);
        $shifts = $this->getShifts();
        $soldiersIds = Soldier::where('course', $this->course)->pluck('id');
        $shifts->each(function ($shifts, $soldierId) use (&$soldiersIds, &$data) {
            $soldiersIds = $soldiersIds->reject(function ($id) use ($soldierId) {
                return $id == $soldierId;
            });
            $data->push($this->getMaxAndDone($soldierId, false, $shifts));
        });
        $soldiersIds->each(function ($soldierId) use (&$data) {
            $data->push($this->getMaxAndDone($soldierId, true));
        });

        return $data
            ->groupBy(fn ($soldierData) => number_format($soldierData->first()->get('max'), 1, '.', ''))
            ->map(
                fn ($items) => $items->map(fn ($item) => collect([$item->keys()->first() => $item->get($item->keys()->first())->get('done')]))
                    ->reduce(function ($carry, $value) {
                        return $carry->merge($value);
                    }, collect())
            )
            ->sortByDesc(fn ($item) => $item->count());
    }

    protected function getShifts()
    {
        return Shift::whereNotNull('soldier_id')
            ->whereHas('soldier', function ($query) {
                $query->where('course', (int) $this->course);
            })
            ->where(function ($query) {
                $query->where('start_date', '<=', $this->date->copy()->endOfMonth())
                    ->where('start_date', '>=', $this->date->copy()->startOfMonth());
            })
            ->where(function ($query) {
                $query->when($this->kind == TaskKind::WEEKEND->value, function ($query) {
                    $query->where('is_weekend', true)
                        ->orWhereHas('task', function ($subQuery) {
                            $subQuery->withTrashed()->where('kind', $this->kind);
                        });
                })
                    ->when($this->kind == 'points', function ($query) {
                        $query->whereNotNull('parallel_weight')
                            ->orWhereHas('task', function ($query) {
                                $query->withTrashed()->where('parallel_weight', '>', 0);
                            });
                    })
                    ->when($this->kind != TaskKind::WEEKEND->value && $this->kind != 'points', function ($query) {
                        $query
                            ->where(function ($query) {
                                $query
                                    ->whereNull('is_weekend')
                                    ->orWhere('is_weekend', false);
                            })
                            ->whereHas('task', function ($subQuery) {
                                $subQuery->withTrashed()->where('kind', $this->kind);
                            });
                    });
            })
            ->get()
            ->groupBy('soldier_id');
    }

    protected function getMaxAndDone($soldierId, $isZero, $shifts = null)
    {
        $soldier = Soldier::find($soldierId);

        return collect([
            $soldier->user->displayName => collect([
                'done' => $isZero ? 0 : $this->howMuch($shifts),
                'max' => $this->max($soldier),
            ]),
        ]);
    }

    protected function howMuch($shifts)
    {
        return match ($this->kind) {
            'points' => $this->howMuchPoints($shifts),
            TaskKind::WEEKEND->value => $this->howMuchWeekends($shifts),
            TaskKind::NIGHT->value => $this->howMuchBy(TaskKind::NIGHT->value, $shifts),
            TaskKind::REGULAR->value => $this->howMuchBy(TaskKind::REGULAR->value, $shifts),
            TaskKind::ALERT->value => $this->howMuchBy(TaskKind::ALERT->value, $shifts),
            TaskKind::INPARALLEL->value => $this->howMuchBy(TaskKind::INPARALLEL->value, $shifts),
        };
    }

    protected function howMuchPoints($shifts)
    {
        return $shifts
            ->sum(fn (Shift $shift) => $shift->parallel_weight != null ? $shift->parallel_weight : $shift->task()->withTrashed()->first()->parallel_weight);
    }

    protected function howMuchWeekends($shifts)
    {
        return $shifts
            ->filter(fn (Shift $shift) => $shift->is_weekend != null ? $shift->is_weekend : ($shift->task()->withTrashed()->first()->kind == TaskKind::WEEKEND->value))
            ->sum(fn (Shift $shift) => $shift->parallel_weight != null ? $shift->parallel_weight : $shift->task()->withTrashed()->first()->parallel_weight);
    }

    protected function howMuchBy($taskKind, $shifts)
    {
        return $shifts
            ->filter(fn (Shift $shift) => $shift->task()->withTrashed()->first()->kind == $taskKind)
            ->count();
    }

    protected function max(Soldier $soldier)
    {
        return match ($this->kind) {
            'points' => $soldier->capacity,
            TaskKind::WEEKEND->value => $soldier->max_weekends,
            TaskKind::NIGHT->value => $soldier->max_nights,
            TaskKind::REGULAR->value => $soldier->max_shifts,
            TaskKind::ALERT->value => $soldier->max_alerts,
            TaskKind::INPARALLEL->value => $soldier->max_in_parallel,
        };
    }
}
