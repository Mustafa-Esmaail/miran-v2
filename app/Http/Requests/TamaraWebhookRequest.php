<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TamaraWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'order_status' => ['nullable', 'string'],
            'order_id' => ['nullable', 'string'],
            'checkout_id' => ['nullable', 'string'],
            'amount' => ['nullable', 'numeric'],
            'data' => ['nullable', 'array'],
            'data.status' => ['nullable', 'string'],
            'data.order_id' => ['nullable', 'string'],
            'data.checkout_id' => ['nullable', 'string'],
        ];
    }
}
