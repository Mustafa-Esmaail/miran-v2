<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Services\EmailLoginLinkService;
use App\Services\Settings\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User && $notifiable->id === $this->order->user_id) {
            return ['database', 'mail'];
        }

        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->order->loadMissing('items');
        $settings = app(SettingsService::class);

        $items = $this->order->items->map(fn ($item) => [
            'title' => $item->title_snapshot,
            'quantity' => $item->quantity,
            'price' => $item->price_amount,
        ])->all();

        return (new MailMessage)
            ->subject(__('web.orders.thanks_title').' — #'.$this->order->order_no)
            ->markdown('mail.payment-success', [
                'greeting' => __('web.student_credentials.greeting', ['name' => $notifiable->name]),
                'thankYouLine' => __('web.orders.thanks_title'),
                'orderNoLabel' => __('web.orders.order_number', ['number' => '']),
                'orderNo' => $this->order->order_no,
                'dateLabel' => __('web.orders.date'),
                'orderDate' => $this->order->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
                'items' => $items,
                'currency' => $this->order->currency,
                'totalLabel' => __('web.cart.total'),
                'total' => $this->order->total_amount,
                'actionUrl' => $this->actionUrl($notifiable),
                'actionText' => __('web.orders.view_details'),
                'salutation' => __('web.mail.salutation', ['app' => config('app.name')]),
                'siteLogoUrl' => $settings->logoUrl(),
                'appName' => config('app.name'),
            ]);
    }

    private function actionUrl(object $notifiable): string
    {
        $orderUrl = route('dashboard.orders.show', ['order_no' => $this->order->order_no]);

        if (! $notifiable instanceof User) {
            return $orderUrl;
        }

        return app(EmailLoginLinkService::class)->urlFor($notifiable, $orderUrl);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_no' => $this->order->order_no,
            'status' => 'paid',
        ];
    }
}
