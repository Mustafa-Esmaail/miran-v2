<?php

namespace App\Http\Controllers\Webhooks;

use App\DTOs\WebhookPayloadDto;
use App\Http\Requests\TamaraWebhookRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class TamaraWebhookController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function __invoke(TamaraWebhookRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = (string) (
            data_get($validated, 'status')
            ?? data_get($validated, 'order_status')
            ?? data_get($validated, 'data.status')
            ?? 'pending'
        );
        $reference = (string) (
            data_get($validated, 'reference')
            ?? data_get($validated, 'checkout_id')
            ?? data_get($validated, 'data.checkout_id')
            ?? data_get($validated, 'order_id')
            ?? data_get($validated, 'data.order_id')
            ?? ''
        );

        $payload = new WebhookPayloadDto(
            'tamara',
            $reference,
            $status,
            $validated
        );

        $this->payments->handleWebhook($payload);

        return response()->json(['ok' => true]);
    }
}
