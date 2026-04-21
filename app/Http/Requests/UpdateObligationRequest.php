<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'amount_expected' => 'sometimes|numeric|min:0',
            'amount_received' => 'sometimes|numeric|min:0',
            'frequency' => 'sometimes|in:monthly,quarterly,one-time',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,partially_paid,received,remitted,overdue',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
        ];
    }
}
