<?php

namespace App\Http\Controllers\Webhooks;

use App\DTOs\WebhookPayloadDto;
use App\Http\Requests\TabbyWebhookRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class TabbyWebhookController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function __invoke(TabbyWebhookRequest $request): JsonResponse
    {
        $payload = new WebhookPayloadDto(
            'tabby',
            $request->string('reference'),
            $request->string('status'),
            $request->validated()
        );

        $this->payments->handleWebhook($payload);

        return response()->json(['ok' => true]);
    }
}
