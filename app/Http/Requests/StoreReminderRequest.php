<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reminder_at' => 'required|date',
            'repeat_type' => 'nullable|in:none,daily,weekly,monthly,yearly',
            'repeat_until' => 'nullable|date|after:reminder_at',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'reminder_at.required' => 'The reminder date and time is required.',
            'repeat_until.after' => 'Repeat until must be after the reminder date.',
        ];
    }
}
