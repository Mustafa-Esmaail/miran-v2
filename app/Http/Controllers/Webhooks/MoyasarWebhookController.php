<?php

namespace App\Http\Controllers\Webhooks;

use App\DTOs\WebhookPayloadDto;
use App\Services\MoyasarService;
use App\Services\PaymentService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoyasarWebhookController
{
    public function __construct(
        private readonly PaymentService  $payments,
        private readonly MoyasarService  $moyasar,
        private readonly SettingsService $settings,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data      = $request->all();
        $invoiceId = (string) ($data['id'] ?? '');
        $orderId   = (int) ($data['metadata']['order_id'] ?? 0);

        // Verify payment result by fetching the invoice directly from Moyasar
        $secretKey = trim((string) ($this->settings->paymentProviderConfig('moyasar')['secret_key'] ?? ''));
        $invoice   = [];

        if ($invoiceId !== '' && $secretKey !== '') {
            $invoice = $this->moyasar->fetchInvoice($invoiceId, $secretKey);

            Log::channel('payments')->info('moyasar.webhook.invoice_fetch', [
                'invoice_id'      => $invoiceId,
                'order_id'        => $orderId,
                'fetched_status'  => $invoice['status'] ?? 'unknown',
                'webhook_status'  => $data['status'] ?? 'unknown',
            ]);
        }

        // Trust the fetched invoice status over the webhook payload
        $verifiedStatus = strtolower((string) ($invoice['status'] ?? $data['status'] ?? 'pending'));

        $dto = new WebhookPayloadDto(
            'moyasar',
            $invoiceId,
            $verifiedStatus === 'paid' ? 'paid' : 'failed',
            array_merge($data, [
                'order_id'        => $orderId,
                'moyasar_invoice' => $invoice,
            ]),
        );

        $this->payments->handleWebhook($dto);

        return response()->json(['ok' => true]);
    }
}
