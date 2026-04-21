<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'obligation_id' => 'required|exists:obligations,id',
            'amount_received' => 'required|numeric|min:0',
            'date_received' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'obligation_id.required' => 'The obligation ID is required.',
            'obligation_id.exists' => 'The selected obligation does not exist.',
            'amount_received.required' => 'The amount received is required.',
            'payment_method.required' => 'The payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }
}
