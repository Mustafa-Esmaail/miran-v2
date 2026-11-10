<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_success_notifies_user_and_admins(): void
    {
        Notification::fake();

        config(['auth.defaults.guard' => 'admin']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => (string) Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        $service = $this->app->make(NotificationService::class);
        $service->notifyPaymentSuccess($order);

        Notification::assertSentTo($user, PaymentSuccessNotification::class);
        Notification::assertSentTo($admin, PaymentSuccessNotification::class);
    }

    public function test_payment_success_does_not_throw_when_notification_channel_fails(): void
    {
        config(['auth.defaults.guard' => 'admin']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => (string) Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        Notification::shouldReceive('send')->andThrow(new \RuntimeException('Mail transport failed'));

        $service = $this->app->make(NotificationService::class);
        $service->notifyPaymentSuccess($order);

        $this->assertTrue(true);
    }
}
