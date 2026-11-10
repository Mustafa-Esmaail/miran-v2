<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
{
    public const MAX_PER_PAGE = 100;

    /**
     * The API client is authenticated and its ability checked by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'updated_since' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.date_format' => 'The from date must be in Y-m-d format.',
            'to.date_format' => 'The to date must be in Y-m-d format.',
            'to.after_or_equal' => 'The to date must be on or after the from date.',
            'per_page.max' => 'per_page may not be greater than '.self::MAX_PER_PAGE.'.',
        ];
    }
}
