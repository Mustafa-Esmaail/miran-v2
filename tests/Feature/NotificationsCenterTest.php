<?php

namespace Tests\Feature;

use App\Livewire\NotificationsCenter;
use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_notification_has_action_link(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORDER-1001',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        $user->notify(new PaymentSuccessNotification($order));

        Livewire::actingAs($user)
            ->test(NotificationsCenter::class)
            ->assertViewHas('groupedNotifications', function (array $groups) use ($order) {
                foreach ($groups as $group) {
                    foreach ($group['items'] as $item) {
                        if ($item['action_url'] === route('dashboard.orders.show', $order->order_no)) {
                            return true;
                        }
                    }
                }

                return false;
            });
    }
}
