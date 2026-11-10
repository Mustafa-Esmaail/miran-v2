<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MoyasarService
{
    private const API_BASE = 'https://api.moyasar.com/v1';

    /**
     * Create a hosted invoice on Moyasar and return its checkout URL.
     *
     * @return array{id: string, url: string, status: string}
     */
    public function createInvoice(Order $order, string $secretKey, string $returnUrl, string $webhookUrl): array
    {
        $payload = [
            'amount'       => (int) $order->total_amount * 100,
            'currency'     => $order->currency ?: 'SAR',
            'description'  => 'Order ' . $order->order_no,
            'callback_url' => $webhookUrl,
            'success_url'  => $returnUrl,
            'back_url'     => $returnUrl,
            'metadata'     => [
                'order_id'  => (string) $order->id,
                'order_no'  => (string) $order->order_no,
            ],
        ];

        Log::channel('payments')->info('moyasar.invoice.request', [
            'order_id'    => $order->id,
            'order_no'    => $order->order_no,
            'amount'      => $payload['amount'],
            'currency'    => $payload['currency'],
            'return_url'  => $returnUrl,
            'webhook_url' => $webhookUrl,
        ]);

        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->post(self::API_BASE . '/invoices', $payload);

        Log::channel('payments')->info('moyasar.invoice.response', [
            'order_id' => $order->id,
            'status'   => $response->status(),
            'body'     => Str::limit($response->body(), 2000),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Moyasar invoice creation failed (HTTP ' . $response->status() . ').');
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch a hosted invoice from Moyasar by its ID and return the full object.
     *
     * @return array<string, mixed>
     */
    public function fetchInvoice(string $invoiceId, string $secretKey): array
    {
        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->get(self::API_BASE . '/invoices/' . $invoiceId);

        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch a single payment from Moyasar by its ID.
     *
     * @return array<string, mixed>
     */
    public function fetchPayment(string $paymentId, string $secretKey): array
    {
        $response = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->get(self::API_BASE . '/payments/' . $paymentId);

        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Normalise a Moyasar payment status to 'paid' | 'failed' | 'pending'.
     */
    public function resolveStatus(string $moyasarStatus): string
    {
        return match (strtolower(trim($moyasarStatus))) {
            'paid'            => 'paid',
            'failed', 'rejected' => 'failed',
            default           => 'pending',
        };
    }
}
