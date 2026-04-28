<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Obligation extends Model
{
    use Notifiable;

    protected $fillable = [
        'title',
        'amount_expected',
        'amount_received',
        'currency',
        'due_date',
        'frequency',
        'status',
        'notes',
        'email',
        'user_id',
    ];

    protected $casts = [
        'amount_expected' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'due_date' => 'date',
    ];

    protected $appends = ['formatted_due_date', 'input_due_date', 'created_at_timestamp', 'updated_at_timestamp'];

    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date instanceof Carbon
            ? $this->due_date->format('d-m-Y')
            : $this->due_date;
    }

    public function getInputDueDateAttribute(): string
    {
        return $this->due_date instanceof Carbon
            ? $this->due_date->format('Y-m-d')
            : $this->due_date;
    }

    public function getCreatedAtTimestampAttribute(): string
    {
        return $this->created_at instanceof Carbon
            ? $this->created_at->format('d-m-Y H:i:s')
            : $this->created_at;
    }

    public function getUpdatedAtTimestampAttribute(): string
    {
        return $this->updated_at instanceof Carbon
            ? $this->updated_at->format('d-m-Y H:i:s')
            : $this->updated_at;
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getOutstandingAttribute(): float
    {
        return (float) ($this->amount_expected - $this->amount_received);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'remitted'
            && $this->status !== 'received'
            && $this->due_date->isPast();
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->amount_received >= $this->amount_expected;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'remitted')
            ->where('due_date', '<', now()->toDateString());
    }
}
