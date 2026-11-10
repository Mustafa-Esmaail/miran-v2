<?php

namespace App\Services;

use App\DTOs\WebhookPayloadDto;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderSeatService $seats,
        private readonly NotificationService $notifications,
        private readonly SettingsService $settings,
        private readonly OrderApprovalService $orderApproval,
    ) {}

    public function completeFreePayment(Order $order): Payment
    {
        if ((int) $order->total_amount > 0) {
            throw new \RuntimeException(__('web.checkout.free_payment_invalid'));
        }

        return DB::transaction(function () use ($order): Payment {
            $payment = Payment::query()
                ->where('order_id', $order->id)
                ->where('provider', 'free')
                ->latest('id')
                ->first();

            if (! $payment) {
                $payment = $this->payments->createPayment([
                    'order_id' => $order->id,
                    'provider' => 'free',
                    'provider_reference' => 'FREE-'.$order->id.'-'.now()->timestamp,
                    'status' => PaymentStatus::Paid,
                    'amount' => (int) $order->total_amount,
                    'payload' => [
                        'source' => 'checkout',
                        'type' => 'free_checkout',
                    ],
                ]);
            } elseif ($payment->status !== PaymentStatus::Paid) {
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'payload' => array_merge($payment->payload ?? [], [
                        'source' => 'checkout',
                        'type' => 'free_checkout',
                    ]),
                ]);
            }

            $this->orderApproval->approve($order);

            $this->logPayment('info', 'payment.free.completed', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });
    }

    private function persistRedirectUrl(Payment $payment, string $redirectUrl): void
    {
        $payment->update([
            'payload' => array_merge($payment->payload ?? [], ['redirect_url' => $redirectUrl]),
        ]);
    }

    public function startPayment(Order $order, string $provider): array
    {
        if (in_array($provider, ['tamara', 'tabby', 'moyasar'], true) && ! $this->settings->paymentProviderIsReady($provider)) {
            $this->logPayment('warning', 'payment.start.rejected', [
                'provider' => $provider,
                'order_id' => $order->id,
                'reason' => 'provider_not_ready',
            ]);
            throw new \RuntimeException(__('web.checkout.no_payment_methods'));
        }

        $providerConfig = $this->settings->paymentProviderConfig($provider);
        $providerReference = strtoupper($provider).'-'.$order->id.'-'.now()->timestamp;
        $this->logPayment('info', 'payment.start.created', [
            'provider' => $provider,
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'total_minor' => $order->total_amount,
            'provider_mode' => $providerConfig['mode'] ?? null,
        ]);

        $payment = $this->payments->createPayment([
            'order_id' => $order->id,
            'provider' => $provider,
            'provider_reference' => $providerReference,
            'status' => PaymentStatus::Pending,
            'amount' => $order->total_amount,
            'payload' => [
                'provider_config' => [
                    'base_url' => $providerConfig['base_url'],
                    'public_key' => $providerConfig['public_key'] !== '',
                ],
            ],
        ]);

        if ($provider === 'tamara') {
            $redirectUrl = $this->createTamaraCheckout($order, $providerConfig, $payment);
            $this->persistRedirectUrl($payment, $redirectUrl);

            return [
                'payment_id' => $payment->id,
                'redirect_url' => $redirectUrl,
            ];
        }

        if ($provider === 'moyasar') {
            $redirectUrl = $this->createMoyasarCheckout($order, $providerConfig, $payment);
            $this->persistRedirectUrl($payment, $redirectUrl);

            return [
                'payment_id' => $payment->id,
                'redirect_url' => $redirectUrl,
            ];
        }

        $redirectUrl = $this->buildRedirectUrl($provider, $providerConfig, $order, $providerReference);
        $this->persistRedirectUrl($payment, $redirectUrl);
        $this->logPayment('info', 'payment.start.redirect', [
            'provider' => $provider,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'redirect_url' => $redirectUrl,
        ]);

        return [
            'payment_id' => $payment->id,
            'redirect_url' => $redirectUrl,
        ];
    }

    public function handleWebhook(WebhookPayloadDto $payload): void
    {
        $this->logPayment('info', 'payment.webhook.received', [
            'provider' => $payload->provider,
            'reference' => $payload->reference,
            'status' => $payload->status,
            'payload' => $payload->payload,
        ]);

        $existing = $this->resolveExistingPayment($payload);

        if ($existing && $existing->status === PaymentStatus::Paid) {
            $this->logPayment('info', 'payment.webhook.ignored', [
                'provider' => $payload->provider,
                'reference' => $payload->reference,
                'reason' => 'already_paid',
            ]);

            return;
        }

        if ($payload->provider === 'tamara') {
            $this->handleTamaraWebhook($payload, $existing);

            return;
        }

        DB::transaction(function () use ($payload, $existing) {
            $orderId = $existing?->order_id ?? $this->resolveLocalOrderIdFromPayload($payload->payload);
            $lockedOrder = $orderId ? Order::query()->where('id', $orderId)->lockForUpdate()->first() : null;

            if ($lockedOrder) {
                $orderStatus = $lockedOrder->status instanceof OrderStatus ? $lockedOrder->status : OrderStatus::tryFrom((string) $lockedOrder->status);
                if ($orderStatus === OrderStatus::Paid) {
                    $this->logPayment('info', 'payment.webhook.ignored', [
                        'provider' => $payload->provider,
                        'reference' => $payload->reference,
                        'order_id' => $lockedOrder->id,
                        'reason' => 'order_already_paid',
                    ]);

                    return;
                }
            }

            $payment = $existing ?: Payment::query()
                ->where('order_id', $payload->payload['order_id'] ?? 0)
                ->where('provider', $payload->provider)
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->first();

            $payment = $payment ?: $this->payments->createPayment([
                'order_id' => $this->resolveLocalOrderIdFromPayload($payload->payload),
                'provider' => $payload->provider,
                'provider_reference' => $payload->reference,
                'status' => PaymentStatus::Pending,
                'amount' => $payload->payload['amount'] ?? 0,
                'payload' => $payload->payload,
            ]);

            $payment->update([
                'provider_reference' => $payload->reference,
                'status' => $payload->status === 'paid' ? PaymentStatus::Paid : PaymentStatus::Failed,
                'payload' => $payload->payload,
            ]);

            $reference = $payload->reference !== '' ? $payload->reference : ($payment->provider_reference ?? 'n/a');
            $order = $lockedOrder ?: $payment->order;
            if (! $order) {
                return;
            }

            if ($payload->status === 'paid') {
                $this->orderApproval->approve($order);
                $this->logPayment('info', 'payment.webhook.processed', [
                    'provider' => $payload->provider,
                    'reference' => $reference,
                    'order_id' => $order->id,
                    'result' => 'paid',
                ]);
            } else {
                $this->orders->markRejected($order, 'Payment failed');
                $this->seats->cancelSeatsForOrder($order);
                $this->notifications->notifyPaymentFailed($order);
                $this->logPayment('warning', 'payment.webhook.processed', [
                    'provider' => $payload->provider,
                    'reference' => $reference,
                    'order_id' => $order->id,
                    'result' => 'failed',
                ]);
            }
        });
    }

    private function resolveExistingPayment(WebhookPayloadDto $payload): ?Payment
    {
        if ($payload->reference !== '') {
            $payment = $this->payments->findByProviderRef($payload->provider, $payload->reference);
            if ($payment) {
                return $payment;
            }
        }

        if ($payload->provider !== 'tamara') {
            return null;
        }

        $tamaraOrderId = (string) (
            data_get($payload->payload, 'order_id')
            ?? data_get($payload->payload, 'data.order_id')
            ?? data_get($payload->payload, 'tamara_order_id')
            ?? ''
        );

        if ($tamaraOrderId === '') {
            return null;
        }

        return Payment::query()
            ->where('provider', 'tamara')
            ->where(function ($query) use ($tamaraOrderId): void {
                $query->where('provider_reference', $tamaraOrderId)
                    ->orWhereJsonContains('payload->tamara_response->order_id', $tamaraOrderId)
                    ->orWhereJsonContains('payload->tamara_authorise_response->order_id', $tamaraOrderId)
                    ->orWhereJsonContains('payload->tamara_capture_response->order_id', $tamaraOrderId);
            })
            ->latest('id')
            ->first();
    }

    private function resolveLocalOrderIdFromPayload(array $payload): ?int
    {
        $orderId = data_get($payload, 'order_id') ?? data_get($payload, 'data.order_id');
        if (! is_numeric($orderId)) {
            return null;
        }

        return (int) $orderId;
    }

    private function handleTamaraWebhook(WebhookPayloadDto $payload, ?Payment $existing): void
    {
        $normalizedStatus = Str::lower($this->resolveTamaraWebhookStatus($payload));
        $tamaraOrderId = (string) (
            data_get($payload->payload, 'order_id')
            ?? data_get($payload->payload, 'data.order_id')
            ?? data_get($existing?->payload, 'tamara_response.order_id')
            ?? ''
        );
        $tamaraCheckoutId = (string) (
            data_get($payload->payload, 'checkout_id')
            ?? data_get($payload->payload, 'data.checkout_id')
            ?? data_get($existing?->payload, 'tamara_response.checkout_id')
            ?? ''
        );
        $providerReference = $tamaraCheckoutId !== '' ? $tamaraCheckoutId : ($payload->reference !== '' ? $payload->reference : $tamaraOrderId);

        DB::transaction(function () use ($payload, $existing, $normalizedStatus, $tamaraOrderId, $providerReference): void {
            $payment = $existing ?: Payment::query()
                ->where('provider', 'tamara')
                ->where('order_id', $this->resolveLocalOrderIdFromPayload($payload->payload) ?? 0)
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->first();

            $payment = $payment ?: $this->payments->createPayment([
                'order_id' => $this->resolveLocalOrderIdFromPayload($payload->payload),
                'provider' => 'tamara',
                'provider_reference' => $providerReference !== '' ? $providerReference : null,
                'status' => PaymentStatus::Pending,
                'amount' => (int) ($payload->payload['amount'] ?? 0),
                'payload' => $payload->payload,
            ]);

            $paymentPayload = array_merge($payment->payload ?? [], [
                'tamara_webhook' => $payload->payload,
                'tamara_webhook_status' => $normalizedStatus,
            ]);
            if ($tamaraOrderId !== '') {
                $paymentPayload['tamara_order_id'] = $tamaraOrderId;
            }

            $payment->update([
                'provider_reference' => $providerReference !== '' ? $providerReference : $payment->provider_reference,
                'payload' => $paymentPayload,
            ]);

            $order = $payment->order;
            if (! $order) {
                $this->logPayment('warning', 'payment.tamara.webhook.missing_order', [
                    'payment_id' => $payment->id,
                    'provider_reference' => $providerReference,
                ]);

                return;
            }

            if (in_array($normalizedStatus, ['captured', 'fully_captured', 'partially_captured', 'paid', 'completed'], true)) {
                $payment->update(['status' => PaymentStatus::Paid]);
                $this->orderApproval->approve($order);

                return;
            }

            if (in_array($normalizedStatus, ['approved', 'authorised', 'authorized'], true)) {
                if ($tamaraOrderId === '') {
                    $this->logPayment('error', 'payment.tamara.webhook.missing_order_id', [
                        'payment_id' => $payment->id,
                        'provider_reference' => $providerReference,
                        'status' => $normalizedStatus,
                    ]);
                    throw new \RuntimeException('Tamara order_id missing for authorise/capture flow.');
                }

                $providerConfig = $this->settings->paymentProviderConfig('tamara');
                [$apiBase, $apiKey] = $this->resolveTamaraCheckoutCredentials($providerConfig);

                if ($normalizedStatus === 'approved') {
                    $authoriseResponse = $this->authoriseTamaraOrder($tamaraOrderId, $apiBase, $apiKey, $order, $payment);
                    $paymentPayload = array_merge($payment->payload ?? [], ['tamara_authorise_response' => $authoriseResponse]);
                    $authoriseOrderStatus = Str::lower((string) data_get($authoriseResponse, 'status'));

                    if (in_array($authoriseOrderStatus, ['fully_captured', 'partially_captured', 'captured'], true)) {
                        $payment->update([
                            'status' => PaymentStatus::Paid,
                            'payload' => $paymentPayload,
                        ]);
                        $this->orderApproval->approve($order);

                        return;
                    }

                    $payment->update([
                        'status' => PaymentStatus::Authorized,
                        'payload' => $paymentPayload,
                    ]);
                }

                $captureResponse = $this->captureTamaraOrder($tamaraOrderId, $apiBase, $apiKey, $order, $payment);
                $paymentPayload = array_merge($payment->payload ?? [], ['tamara_capture_response' => $captureResponse]);
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'payload' => $paymentPayload,
                ]);

                $this->orderApproval->approve($order);

                return;
            }

            if (in_array($normalizedStatus, ['failed', 'rejected', 'declined', 'canceled', 'cancelled', 'expired'], true)) {
                $payment->update(['status' => PaymentStatus::Failed]);
                $this->orders->markRejected($order, 'Payment failed');
                $this->seats->cancelSeatsForOrder($order);
                $this->notifications->notifyPaymentFailed($order);

                return;
            }

            $payment->update(['status' => PaymentStatus::Pending]);
            $this->logPayment('info', 'payment.tamara.webhook.pending', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'status' => $normalizedStatus,
                'provider_reference' => $providerReference,
            ]);
        });
    }

    private function resolveTamaraWebhookStatus(WebhookPayloadDto $payload): string
    {
        $status = (string) (
            $payload->status
            ?: data_get($payload->payload, 'status')
            ?: data_get($payload->payload, 'order_status')
            ?: data_get($payload->payload, 'data.status')
            ?: 'pending'
        );

        return trim($status);
    }

    /**
     * @param  array{mode?:string,api_key?:string,init_api_key?:string}  $providerConfig
     * @return array{0:string,1:string}
     */
    private function resolveTamaraApiCredentials(array $providerConfig): array
    {
        $mode = Str::lower(trim((string) ($providerConfig['mode'] ?? 'test')));
        $apiBase = $mode === 'live' ? 'https://api.tamara.co' : 'https://api-sandbox.tamara.co';
        $apiKey = trim((string) ($providerConfig['api_key'] ?? ''));

        if ($apiKey === '') {
            $apiKey = trim((string) ($providerConfig['init_api_key'] ?? ''));
        }

        if ($apiKey === '') {
            throw new \RuntimeException(__('web.checkout.no_payment_methods'));
        }

        return [$apiBase, $apiKey];
    }

    /**
     * @param  array{mode?:string,api_key?:string,init_api_key?:string}  $providerConfig
     * @return array{0:string,1:string}
     */
    private function resolveTamaraCheckoutCredentials(array $providerConfig): array
    {
        $mode = Str::lower(trim((string) ($providerConfig['mode'] ?? 'test')));
        $apiBase = $mode === 'live' ? 'https://api.tamara.co' : 'https://api-sandbox.tamara.co';
        $initApiKey = trim((string) ($providerConfig['init_api_key'] ?? ''));

        if ($initApiKey === '') {
            $initApiKey = trim((string) ($providerConfig['api_key'] ?? ''));
        }

        if ($initApiKey === '') {
            throw new \RuntimeException(__('web.checkout.no_payment_methods'));
        }

        return [$apiBase, $initApiKey];
    }

    /**
     * @return array<string, mixed>
     */
    private function authoriseTamaraOrder(
        string $tamaraOrderId,
        string $apiBase,
        string $apiKey,
        Order $order,
        Payment $payment
    ): array {
        $endpoint = $apiBase.'/orders/'.$tamaraOrderId.'/authorise';
        $this->logPayment('info', 'payment.tamara.authorise.request', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => $payment->id,
            'endpoint' => $endpoint,
            'tamara_order_id' => $tamaraOrderId,
        ]);

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->post($endpoint);
        } catch (Throwable $exception) {
            $this->logPayment('error', 'payment.tamara.authorise.exception', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'tamara_order_id' => $tamaraOrderId,
                'error' => $exception->getMessage(),
            ]);
            throw new \RuntimeException('Tamara authorise failed.');
        }

        $this->logPayment('info', 'payment.tamara.authorise.response', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => $payment->id,
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 4000),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Tamara authorise failed.');
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function captureTamaraOrder(
        string $tamaraOrderId,
        string $apiBase,
        string $apiKey,
        Order $order,
        Payment $payment
    ): array {
        $order->loadMissing('items');
        $endpoint = $apiBase.'/payments/capture';
        $requestPayload = [
            'order_id' => $tamaraOrderId,
            'total_amount' => $this->asTamaraAmount($order->total_amount, $order->currency),
            'items' => $order->items->map(function ($item) use ($order): array {
                $lineTotal = (int) ($item->price_amount * $item->quantity);

                return [
                    'name' => (string) ($item->title_snapshot ?: 'Item #'.$item->id),
                    'type' => $this->tamaraItemType(),
                    'reference_id' => (string) $item->id,
                    'sku' => (string) (data_get($item->metadata_snapshot, 'sku') ?: 'ITEM-'.$item->id),
                    'quantity' => (int) $item->quantity,
                    'discount_amount' => $this->asTamaraAmount(0, $order->currency),
                    'tax_amount' => $this->asTamaraAmount(0, $order->currency),
                    'unit_price' => $this->asTamaraAmount((int) $item->price_amount, $order->currency),
                    'total_amount' => $this->asTamaraAmount($lineTotal, $order->currency),
                ];
            })->values()->all(),
            'discount_amount' => $this->asTamaraAmount($order->discount_amount, $order->currency),
            'shipping_amount' => $this->asTamaraAmount(0, $order->currency),
            'tax_amount' => $this->asTamaraAmount(0, $order->currency),
        ];

        $this->logPayment('info', 'payment.tamara.capture.request', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => $payment->id,
            'endpoint' => $endpoint,
            'tamara_order_id' => $tamaraOrderId,
            'payload' => $requestPayload,
        ]);

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->post($endpoint, $requestPayload);
        } catch (Throwable $exception) {
            $this->logPayment('error', 'payment.tamara.capture.exception', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'tamara_order_id' => $tamaraOrderId,
                'error' => $exception->getMessage(),
            ]);
            throw new \RuntimeException('Tamara capture failed.');
        }

        $this->logPayment('info', 'payment.tamara.capture.response', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => $payment->id,
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 4000),
        ]);

        if (! $response->successful()) {
            $errorCode = data_get($response->json(), 'errors.0.error_code');
            $attemptedNewState = data_get($response->json(), 'errors.0.data.new_state');

            if ($response->status() === 409 && $errorCode === 'transition_not_allowed' && $attemptedNewState === 'fully_captured') {
                $this->logPayment('info', 'payment.tamara.capture.already_captured', [
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'payment_id' => $payment->id,
                    'tamara_order_id' => $tamaraOrderId,
                ]);

                return $response->json() ?? [];
            }

            throw new \RuntimeException('Tamara capture failed.');
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array{enabled:bool,base_url:string,public_key:string,secret_key:string,webhook_secret:string}  $providerConfig
     */
    private function buildRedirectUrl(string $provider, array $providerConfig, Order $order, string $providerReference): string
    {
        $base = trim($providerConfig['base_url']);
        if ($base === '') {
            return route('dashboard.orders.show', ['order_no' => $order->order_no]);
        }

        $separator = str_contains($base, '?') ? '&' : '?';

        return $base.$separator.http_build_query([
            'provider' => $provider,
            'order_no' => $order->order_no,
            'reference' => $providerReference,
            'amount' => $order->total_amount,
        ]);
    }

    /**
     * @param  array{enabled:bool,mode?:string,publishable_key?:string,secret_key?:string}  $providerConfig
     */
    private function createMoyasarCheckout(Order $order, array $providerConfig, Payment $payment): string
    {
        $secretKey = trim((string) ($providerConfig['secret_key'] ?? ''));

        if ($secretKey === '') {
            throw new \RuntimeException(__('web.checkout.no_payment_methods'));
        }

        $returnUrl = route('dashboard.payments.return', ['provider' => 'moyasar', 'order_no' => $order->order_no]);
        $webhookUrl = url('/api/webhooks/moyasar');

        $moyasar = app(MoyasarService::class);
        $invoice = $moyasar->createInvoice($order, $secretKey, $returnUrl, $webhookUrl);

        $invoiceUrl = (string) ($invoice['url'] ?? '');
        $invoiceId = (string) ($invoice['id'] ?? '');

        if ($invoiceUrl === '') {
            $this->logPayment('error', 'payment.moyasar.checkout.missing_url', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'response' => $invoice,
            ]);
            throw new \RuntimeException('Moyasar invoice URL missing in response.');
        }

        $payment->update([
            'provider_reference' => $invoiceId !== '' ? $invoiceId : $payment->provider_reference,
            'payload' => array_merge($payment->payload ?? [], ['moyasar_invoice' => $invoice]),
        ]);

        return $invoiceUrl;
    }

    /**
     * @param  array{enabled:bool,mode?:string,api_key?:string,base_url:string,public_key:string,secret_key:string,webhook_secret:string}  $providerConfig
     */
    private function createTamaraCheckout(Order $order, array $providerConfig, Payment $payment): string
    {
        [$apiBase, $apiKey] = $this->resolveTamaraApiCredentials($providerConfig);
        $mode = Str::lower(trim((string) ($providerConfig['mode'] ?? 'test')));

        $order->loadMissing('items', 'user');
        $user = $order->user;
        $fullName = trim((string) ($user?->name ?? 'Customer'));
        $parts = preg_split('/\s+/u', $fullName, 2) ?: [];
        $firstName = $parts[0] ?? 'Customer';
        $lastName = $parts[1] ?? $firstName;
        $phone = $this->formatTamaraPhone((string) ($user?->phone ?? '500000000'));
        $endpoint = $apiBase.'/checkout';
        $requestPayload = [
            'total_amount' => $this->asTamaraAmount($order->total_amount, $order->currency),
            'shipping_amount' => $this->asTamaraAmount(0, $order->currency),
            'tax_amount' => $this->asTamaraAmount(0, $order->currency),
            'order_reference_id' => (string) $payment->provider_reference,
            'order_number' => (string) $order->order_no,
            'discount' => [
                'name' => 'Order discount',
                'amount' => $this->asTamaraAmount($order->discount_amount, $order->currency),
            ],
            'items' => $order->items->map(function ($item) use ($order): array {
                $lineTotal = (int) ($item->price_amount * $item->quantity);

                return [
                    'name' => (string) ($item->title_snapshot ?: 'Item #'.$item->id),
                    'type' => $this->tamaraItemType(),
                    'reference_id' => (string) $item->id,
                    'sku' => (string) (data_get($item->metadata_snapshot, 'sku') ?: 'ITEM-'.$item->id),
                    'quantity' => (int) $item->quantity,
                    'discount_amount' => $this->asTamaraAmount(0, $order->currency),
                    'tax_amount' => $this->asTamaraAmount(0, $order->currency),
                    'unit_price' => $this->asTamaraAmount((int) $item->price_amount, $order->currency),
                    'total_amount' => $this->asTamaraAmount($lineTotal, $order->currency),
                ];
            })->values()->all(),
            'consumer' => [
                'email' => (string) ($user?->email ?? 'customer@example.com'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $phone,
            ],
            'country_code' => 'SA',
            'description' => 'Order '.$order->order_no,
            'merchant_url' => [
                'cancel' => route('dashboard.payments.return', ['provider' => 'tamara', 'order_no' => $order->order_no]).'?payment=cancel',
                'failure' => route('dashboard.payments.return', ['provider' => 'tamara', 'order_no' => $order->order_no]).'?payment=failure',
                'success' => route('dashboard.payments.return', ['provider' => 'tamara', 'order_no' => $order->order_no]).'?payment=success',
                'notification' => url('/api/webhooks/tamara'),
            ],
            'billing_address' => [
                'city' => 'Riyadh',
                'country_code' => 'SA',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'line1' => 'Riyadh',
                'line2' => '',
                'phone_number' => $phone,
                'region' => 'Riyadh',
            ],
            'shipping_address' => [
                'city' => 'Riyadh',
                'country_code' => 'SA',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'line1' => 'Riyadh',
                'line2' => '',
                'phone_number' => $phone,
                'region' => 'Riyadh',
            ],
            'platform' => config('app.name'),
            'is_mobile' => false,
            'locale' => app()->getLocale() === 'ar' ? 'ar_SA' : 'en_US',
            'risk_assessment' => $this->buildTamaraRiskAssessment($user, $order),
            'additional_data' => $this->buildTamaraAdditionalData($order),
        ];
        $configuredPaymentType = trim((string) ($providerConfig['payment_type'] ?? ''));
        if ($configuredPaymentType !== '') {
            $requestPayload['payment_type'] = strtoupper($configuredPaymentType);

            $configuredInstallments = (int) ($providerConfig['instalments'] ?? 0);
            if ($configuredInstallments > 0) {
                $requestPayload['instalments'] = $configuredInstallments;
            }
        }

        $this->logPayment('info', 'payment.tamara.checkout.request', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => $payment->id,
            'endpoint' => $endpoint,
            'mode' => $mode,
            'payload' => $requestPayload,
        ]);

        $response = $this->sendTamaraCheckoutRequest($endpoint, $apiKey, $requestPayload, $order, $payment);

        if (
            ! $response->successful()
            && $this->tamaraHasUnsupportedPaymentTypeError($response->json() ?? [])
            && array_key_exists('payment_type', $requestPayload)
        ) {
            $fallbackPayload = $requestPayload;
            $fromPaymentType = (string) ($fallbackPayload['payment_type'] ?? '');
            unset($fallbackPayload['payment_type']);
            unset($fallbackPayload['instalments']);

            $this->logPayment('warning', 'payment.tamara.checkout.retry_with_fallback_payment_type', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'from_payment_type' => $fromPaymentType,
                'to_payment_type' => 'auto',
            ]);

            $response = $this->sendTamaraCheckoutRequest($endpoint, $apiKey, $fallbackPayload, $order, $payment);
        }

        $this->logPayment('info', 'payment.tamara.checkout.response', [
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'payment_id' => $payment->id,
            'status' => $response->status(),
            'body' => Str::limit($response->body(), 4000),
        ]);

        if (! $response->successful()) {
            $this->logPayment('error', 'payment.tamara.checkout.failed', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 4000),
            ]);
            throw new \RuntimeException('Tamara checkout initialization failed.');
        }

        $payload = $response->json() ?? [];
        $checkoutUrl = (string) ($payload['checkout_url'] ?? $payload['url'] ?? '');
        $checkoutId = (string) ($payload['checkout_id'] ?? $payload['order_id'] ?? '');

        if ($checkoutUrl === '') {
            $this->logPayment('error', 'payment.tamara.checkout.missing_url', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'response' => $payload,
            ]);
            throw new \RuntimeException('Tamara checkout URL missing in response.');
        }

        $payment->update([
            'provider_reference' => $checkoutId !== '' ? $checkoutId : $payment->provider_reference,
            'payload' => array_merge($payment->payload ?? [], ['tamara_response' => $payload]),
        ]);

        return $checkoutUrl;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendTamaraCheckoutRequest(
        string $endpoint,
        string $apiKey,
        array $payload,
        Order $order,
        Payment $payment
    ): \Illuminate\Http\Client\Response {
        try {
            return Http::acceptJson()
                ->withToken($apiKey)
                ->post($endpoint, $payload);
        } catch (Throwable $exception) {
            $this->logPayment('error', 'payment.tamara.checkout.exception', [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'payment_id' => $payment->id,
                'endpoint' => $endpoint,
                'error' => $exception->getMessage(),
                'class' => $exception::class,
            ]);
            throw new \RuntimeException('Tamara checkout initialization failed.');
        }
    }

    /**
     * @param  array<string, mixed>  $responseBody
     */
    private function tamaraItemType(): string
    {
        return 'Digital';
    }

    private function tamaraHasUnsupportedPaymentTypeError(array $responseBody): bool
    {
        $errors = data_get($responseBody, 'errors', []);
        if (! is_array($errors)) {
            return false;
        }

        foreach ($errors as $error) {
            if (! is_array($error)) {
                continue;
            }

            if (strtolower((string) ($error['error_code'] ?? '')) === 'payment_type_unsupported') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string|int|float|bool>
     */
    private function buildTamaraRiskAssessment(?User $user, Order $order): array
    {
        $accountCreatedAt = $user?->created_at ?? $order->created_at ?? now();
        $customerDob = $user?->birth_date;
        $customerAge = $customerDob?->age;
        $gender = match (strtolower((string) $user?->gender)) {
            'male' => 'Male',
            'female' => 'Female',
            default => 'Male',
        };
        $deliveredOrdersCount = $user
            ? $user->orders()->where('status', 'paid')->count()
            : 0;
        $totalOrdersCount = $user ? $user->orders()->count() : 0;
        $firstOrder = $user
            ? $user->orders()->oldest('created_at')->first()
            : null;
        $totalLtv = $user
            ? (float) $user->orders()->where('status', 'paid')->sum('total_amount')
            : 0.0;
        $lastPaidOrder = $user
            ? $user->orders()->where('status', 'paid')->latest('created_at')->first()
            : null;

        return [
            'customer_age' => $customerAge ?? 22,
            'customer_dob' => $customerDob?->format('d-m-Y') ?? now()->subYears(21)->format('d-m-Y'),
            'customer_gender' => $gender,
            'customer_nationality' => strtoupper((string) ($user?->nationality ?: 'SA')),
            'is_premium_customer' => false,
            'is_existing_customer' => $totalOrdersCount > 0,
            'is_guest_user' => false,
            'account_creation_date' => $accountCreatedAt->format('d-m-Y'),
            'platform_account_creation_date' => $accountCreatedAt->format('d-m-Y'),
            'date_of_first_transaction' => $firstOrder?->created_at?->format('d-m-Y') ?? $accountCreatedAt->format('d-m-Y'),
            'is_card_on_file' => false,
            'is_COD_customer' => false,
            'has_delivered_order' => $deliveredOrdersCount > 0,
            'is_phone_verified' => false,
            'is_fraudulent_customer' => false,
            'total_ltv' => round($totalLtv, 2),
            'total_order_count' => $totalOrdersCount,
            'order_amount_last3months' => round((float) ($user?->orders()->where('created_at', '>=', now()->subMonths(3))->sum('total_amount') ?? 0), 2),
            'order_count_last3months' => $user?->orders()->where('created_at', '>=', now()->subMonths(3))->count() ?? 0,
            'last_order_date' => $lastPaidOrder?->created_at?->format('d-m-Y') ?? $accountCreatedAt->format('d-m-Y'),
            'last_order_amount' => round((float) ($lastPaidOrder?->total_amount ?? 0), 2),
            'reward_program_enrolled' => true,
            'reward_program_points' => 0,
            'phone_verified' => false,
        ];
    }

    /**
     * @return array<string, string|int|float>
     */
    private function buildTamaraAdditionalData(Order $order): array
    {
        return [
            'delivery_method' => 'digital delivery',
            'pickup_store' => 'Online',
            'store_code' => 'MIRAN-LMS',
            'vendor_amount' => 0,
            'merchant_settlement_amount' => 0,
            'vendor_reference_code' => (string) $order->order_no,
        ];
    }

    /**
     * @return array{amount:float,currency:string}
     */
    private function asTamaraAmount(int $minorAmount, string $currency): array
    {
        return [
            'amount' => round((float) $minorAmount, 2),
            'currency' => $currency,
        ];
    }

    private function formatTamaraPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '500000000';
        }

        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * @param  'debug'|'info'|'notice'|'warning'|'error'|'critical'|'alert'|'emergency'  $level
     * @param  array<string, mixed>  $context
     */
    private function logPayment(string $level, string $message, array $context = []): void
    {
        match ($level) {
            'debug' => Log::debug($message, $context),
            'info' => Log::info($message, $context),
            'notice' => Log::notice($message, $context),
            'warning' => Log::warning($message, $context),
            'error' => Log::error($message, $context),
            'critical' => Log::critical($message, $context),
            'alert' => Log::alert($message, $context),
            default => Log::emergency($message, $context),
        };

        Log::channel('payments')->log($level, $message, $context);
    }
}
