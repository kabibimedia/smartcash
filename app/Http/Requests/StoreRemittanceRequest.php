<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemittanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receipt_id' => 'required|exists:receipts,id',
            'amount_paid' => 'required|numeric|min:0',
            'date_paid' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,other',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_id.required' => 'The receipt ID is required.',
            'receipt_id.exists' => 'The selected receipt does not exist.',
            'amount_paid.required' => 'The amount paid is required.',
            'payment_method.required' => 'The payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }
}
