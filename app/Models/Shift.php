<?php

namespace App\Models;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime:Y-m-d',
        'end_date' => 'datetime:Y-m-d',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function getTaskNameAttribute()
    {
        $user_name = User::where('userable_id', $this->soldier_id)->get(['first_name', 'last_name']);

        return $this->task?->name.' - '.$user_name->first()?->first_name.' '.$user_name->first()?->last_name;
    }

    public function getTaskColorAttribute()
    {
        return $this->task?->color;
    }

    public static function getSchema(): array
    {
        return [
            Section::make([
                Placeholder::make('')
                    ->content(content: fn (Shift $shift) => $shift->task_name)
                    ->inlineLabel(),
                DateTimePicker::make('start_date')->required(),
                DateTimePicker::make('end_date')->required(),
            ]),

        ];
    }

    public static function getTitle(): string|Htmlable
    {
        return __('Shift');
    }
}
