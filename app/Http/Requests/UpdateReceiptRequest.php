<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'obligation_id' => 'sometimes|exists:obligations,id',
            'amount_received' => 'sometimes|numeric|min:0',
            'date_received' => 'sometimes|date',
            'payment_method' => 'sometimes|in:cash,bank_transfer,mobile_money,cheque,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'obligation_id.exists' => 'The selected obligation does not exist.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }
}
