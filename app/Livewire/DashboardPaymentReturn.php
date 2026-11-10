<?php

namespace App\Livewire;

use App\DTOs\WebhookPayloadDto;
use App\Models\Order;
use App\Services\MoyasarService;
use App\Services\PaymentService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Livewire\Component;

class DashboardPaymentReturn extends Component
{
    public string $provider;

    public string $order_no;

    public ?string $status = null;

    public function mount(
        string $provider,
        string $order_no,
        Request $request,
        PaymentService $payments,
        MoyasarService $moyasar,
        SettingsService $settings,
    ): void {
        $this->provider = strtolower(trim($provider));
        $this->order_no = $order_no;

        $order = Order::query()
            ->where('user_id', auth()->id())
            ->where('order_no', $this->order_no)
            ->firstOrFail();

        $paymentFlow = strtolower((string) $request->query('payment', ''));
        $paymentStatus = strtolower((string) $request->query('paymentStatus', ''));
        $tamaraOrderId = trim((string) $request->query('orderId', ''));

        if (
            $this->provider === 'tamara'
            && $paymentFlow === 'success'
            && $paymentStatus === 'approved'
            && $tamaraOrderId !== ''
        ) {
            $payments->handleWebhook(new WebhookPayloadDto(
                'tamara',
                '',
                'approved',
                [
                    'order_id' => $tamaraOrderId,
                    'status' => 'approved',
                ],
            ));
        }

        // Moyasar success_url redirect: ?id={payment_id}&status=paid|failed&message=APPROVED
        $moyasarPaymentId = trim((string) $request->query('id', ''));
        $moyasarStatus = strtolower((string) $request->query('status', ''));

        if ($this->provider === 'moyasar' && $moyasarPaymentId !== '') {
            // Verify the payment status directly with Moyasar â€” don't trust the URL param alone
            $secretKey = trim((string) ($settings->paymentProviderConfig('moyasar')['secret_key'] ?? ''));
            $fetchedPayment = [];

            if ($secretKey !== '') {
                $fetchedPayment = $moyasar->fetchPayment($moyasarPaymentId, $secretKey);
            }

            $verifiedStatus = strtolower((string) ($fetchedPayment['status'] ?? $moyasarStatus));

            $payments->handleWebhook(new WebhookPayloadDto(
                'moyasar',
                $moyasarPaymentId,
                $verifiedStatus === 'paid' ? 'paid' : 'failed',
                [
                    'id' => $moyasarPaymentId,
                    'status' => $verifiedStatus,
                    'message' => (string) $request->query('message', ''),
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'moyasar_payment' => $fetchedPayment,
                ],
            ));

            $paymentFlow = $verifiedStatus;
        }

        $this->status = $paymentFlow !== '' ? $paymentFlow : ($paymentStatus !== '' ? $paymentStatus : null);

        if (in_array($this->status, ['success', 'paid'], true)) {
            session()->flash('gtm_purchase_order_id', $order->id);
            $this->redirectRoute('payment-success', ['order_no' => $order->order_no], navigate: false);

            return;
        }

        $this->redirectRoute('payment-failed', ['order_no' => $order->order_no], navigate: false);
    }

    public function render(): View
    {
        return view('livewire.dashboard-payment-return')
            ->layout('layouts.app');
    }
}
