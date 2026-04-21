<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Remittance extends Model
{
    use Notifiable;

    protected $fillable = [
        'receipt_id',
        'amount_paid',
        'date_paid',
        'payment_method',
        'reference',
        'notes',
        'user_id',
        'email',
        'image_path',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'date_paid' => 'date',
    ];

    protected $appends = ['formatted_date_paid', 'input_date_paid', 'created_at_timestamp', 'updated_at_timestamp', 'image_url'];

    public function getFormattedDatePaidAttribute(): string
    {
        return $this->date_paid instanceof Carbon
            ? $this->date_paid->format('d-m-Y')
            : $this->date_paid;
    }

    public function getInputDatePaidAttribute(): string
    {
        return $this->date_paid instanceof Carbon
            ? $this->date_paid->format('Y-m-d')
            : $this->date_paid;
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

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getObligation(): Obligation
    {
        return $this->receipt->obligation;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }
}
