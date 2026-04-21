<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Reminder extends Model
{
    use Notifiable;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'reminder_at',
        'repeat_type',
        'repeat_until',
        'is_active',
        'email',
    ];

    protected $casts = [
        'reminder_at' => 'datetime',
        'repeat_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['formatted_reminder_at', 'input_reminder_at', 'formatted_created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedReminderAtAttribute(): string
    {
        return $this->reminder_at instanceof Carbon
            ? $this->reminder_at->format('d-m-Y H:i')
            : $this->reminder_at;
    }

    public function getInputReminderAtAttribute(): string
    {
        return $this->reminder_at instanceof Carbon
            ? $this->reminder_at->format('Y-m-d\TH:i')
            : $this->reminder_at;
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at instanceof Carbon
            ? $this->created_at->format('d-m-Y H:i')
            : $this->created_at;
    }

    public function getNextReminderDate(): ?Carbon
    {
        if (! $this->is_active || ! $this->reminder_at) {
            return null;
        }

        $next = $this->reminder_at->copy();

        if ($next->isPast() && $this->repeat_type) {
            while ($next->isPast()) {
                $next = $this->getNextRepeatDate($next);
            }

            if ($this->repeat_until && $next->isAfter($this->repeat_until)) {
                return null;
            }
        }

        return $next;
    }

    private function getNextRepeatDate(Carbon $date): Carbon
    {
        return match ($this->repeat_type) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            'yearly' => $date->addYear(),
            default => $date,
        };
    }
}
