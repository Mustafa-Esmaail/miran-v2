<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TabbyWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference' => ['required', 'string'],
            'status' => ['required', 'string'],
            'order_id' => ['required', 'integer'],
            'amount' => ['required', 'integer'],
        ];
    }
}
