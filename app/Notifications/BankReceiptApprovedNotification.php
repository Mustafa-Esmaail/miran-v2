<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class BankReceiptApprovedNotification extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_no' => $this->order->order_no,
            'status' => 'approved',
        ];
    }
}
