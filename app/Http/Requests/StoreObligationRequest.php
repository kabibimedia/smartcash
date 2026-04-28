<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'amount_expected' => 'required|numeric|min:0',
            'currency' => 'nullable|in:GHS,USD,EUR,GBP,NGN',
            'frequency' => 'required|in:monthly,quarterly,one-time',
            'due_date' => 'required|date',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The title is required.',
            'amount_expected.required' => 'The expected amount is required.',
            'frequency.required' => 'The frequency is required.',
            'frequency.in' => 'Frequency must be monthly, quarterly, or one-time.',
            'due_date.required' => 'The due date is required.',
        ];
    }
}
