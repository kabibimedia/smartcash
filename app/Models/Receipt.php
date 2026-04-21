<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Receipt extends Model
{
    use Notifiable;

    protected $fillable = [
        'obligation_id',
        'amount_received',
        'date_received',
        'payment_method',
        'reference',
        'notes',
        'user_id',
        'email',
        'image_path',
    ];

    protected $casts = [
        'amount_received' => 'decimal:2',
        'date_received' => 'date',
    ];

    protected $appends = ['formatted_date_received', 'input_date_received', 'created_at_timestamp', 'updated_at_timestamp', 'image_url'];

    public function getFormattedDateReceivedAttribute(): string
    {
        return $this->date_received instanceof Carbon
            ? $this->date_received->format('d-m-Y')
            : $this->date_received;
    }

    public function getInputDateReceivedAttribute(): string
    {
        return $this->date_received instanceof Carbon
            ? $this->date_received->format('Y-m-d')
            : $this->date_received;
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

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(Obligation::class);
    }

    public function remittances(): HasMany
    {
        return $this->hasMany(Remittance::class);
    }

    public function getTotalRemittedAttribute(): float
    {
        return (float) $this->remittances()->sum('amount_paid');
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->amount_received - $this->total_remitted);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
