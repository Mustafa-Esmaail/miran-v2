<?php

namespace Tests\Feature\Api\V1;

use App\Enums\OrderStatus;
use App\Models\ApiClient;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'mlms_test_key_0123456789abcdefghijklmnopqrstuvwxyzAB';

    protected function setUp(): void
    {
        parent::setUp();

        ApiClient::factory()->withKey(self::KEY)->create();
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return ['X-API-Key' => self::KEY];
    }

    private function orderWithItem(array $attributes = []): Order
    {
        $order = Order::factory()->create($attributes);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => 1,
            'title_snapshot' => 'Laravel Bootcamp',
            'currency' => 'SAR',
            'price_amount' => 5000,
            'quantity' => 2,
        ]);

        return $order;
    }

    public function test_it_lists_orders_with_customer_and_items(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $order = $this->orderWithItem([
            'user_id' => $user->id,
            'customer_name' => 'Sara Ali',
            'order_no' => 'ORD-1001',
            'status' => OrderStatus::Paid,
            'notes' => 'internal note',
        ]);

        $response = $this->getJson('/api/v1/orders', $this->headers())
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.order_no', 'ORD-1001')
            ->assertJsonPath('data.0.status', 'paid')
            ->assertJsonPath('data.0.total_amount', $order->total_amount)
            ->assertJsonPath('data.0.customer.name', 'Sara Ali')
            ->assertJsonPath('data.0.customer.email', 'buyer@example.com')
            ->assertJsonPath('data.0.items.0.title', 'Laravel Bootcamp')
            ->assertJsonPath('data.0.items.0.quantity', 2)
            ->assertJsonPath('data.0.items.0.price_amount', 5000);

        $orderJson = $response->json('data.0');
        foreach (['id', 'user_id', 'notes', 'coupon_id', 'referred_by_employee_id', 'customer_phone'] as $hidden) {
            $this->assertArrayNotHasKey($hidden, $orderJson);
        }

        foreach (['purchasable_id', 'purchasable_type', 'metadata_snapshot', 'order_id'] as $hidden) {
            $this->assertArrayNotHasKey($hidden, $orderJson['items'][0]);
        }
    }

    public function test_it_returns_contact_marketing_coupon_and_payment_details(): void
    {
        $team = Team::factory()->create(['name' => 'Team Riyadh']);
        $employee = Employee::factory()->create(['team_id' => $team->id, 'name' => 'Ahmed Saleh', 'employee_code' => 'EMP-014']);
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'October'],
            'code' => 'OCT10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);
        $order = $this->orderWithItem([
            'order_no' => 'ORD-FULL',
            'customer_name' => 'سارة علي أحمد',
            'customer_phone' => '512345678',
            'referred_by_employee_id' => $employee->id,
            'marketing_source' => 'snapchat',
            'coupon_id' => $coupon->id,
            'status' => OrderStatus::Paid,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'moyasar',
            'status' => 'paid',
            'amount' => $order->total_amount,
        ]);

        $this->getJson('/api/v1/orders/ORD-FULL', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.customer.first_name', 'سارة')
            ->assertJsonPath('data.customer.last_name', 'علي أحمد')
            ->assertJsonPath('data.customer.phone', '512345678')
            ->assertJsonPath('data.customer.phone_international', '+966512345678')
            ->assertJsonPath('data.marketing.code', 'EMP-014')
            ->assertJsonPath('data.marketing.team', 'Team Riyadh')
            ->assertJsonPath('data.marketing.employee', 'Ahmed Saleh')
            ->assertJsonPath('data.marketing.employee_id', $employee->id)
            ->assertJsonPath('data.marketing.employee_code', 'EMP-014')
            ->assertJsonPath('data.marketing.source', 'snapchat')
            ->assertJsonPath('data.coupon.code', 'OCT10')
            ->assertJsonPath('data.coupon.type', 'percent')
            ->assertJsonPath('data.coupon.value', 10)
            ->assertJsonPath('data.payment.method', 'moyasar')
            ->assertJsonPath('data.payment.paid_at', $payment->updated_at->toIso8601String());
    }

    public function test_order_without_coupon_employee_or_payment_returns_nulls(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-BARE', 'customer_phone' => null, 'marketing_source' => 'instagram']);

        $this->getJson('/api/v1/orders/ORD-BARE', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.customer.phone', null)
            ->assertJsonPath('data.customer.phone_international', null)
            ->assertJsonPath('data.marketing.code', 'instagram')
            ->assertJsonPath('data.marketing.source', 'instagram')
            ->assertJsonPath('data.marketing.employee', null)
            ->assertJsonPath('data.marketing.employee_id', null)
            ->assertJsonPath('data.marketing.employee_code', null)
            ->assertJsonPath('data.marketing.team', null)
            ->assertJsonPath('data.coupon', null)
            ->assertJsonPath('data.payment.method', null)
            ->assertJsonPath('data.payment.paid_at', null);
    }

    public function test_soft_deleted_coupon_is_still_returned(): void
    {
        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Old'],
            'code' => 'FIX200',
            'type' => 'fixed',
            'applies_to' => 'both',
            'value' => 200,
            'is_active' => true,
        ]);
        $this->orderWithItem(['order_no' => 'ORD-OLDCOUPON', 'coupon_id' => $coupon->id]);
        $coupon->delete();

        $this->getJson('/api/v1/orders/ORD-OLDCOUPON', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.coupon.type', 'fixed')
            ->assertJsonPath('data.coupon.value', 200);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phoneFormats(): array
    {
        return [
            'site format' => ['512345678', '+966512345678'],
            'leading zero' => ['0512345678', '+966512345678'],
            'country code' => ['966512345678', '+966512345678'],
            'international with plus' => ['+966 51 234 5678', '+966512345678'],
            'other country kept as is' => ['+201001234567', '+201001234567'],
        ];
    }

    #[DataProvider('phoneFormats')]
    public function test_it_normalizes_international_phone(string $stored, string $expected): void
    {
        $this->orderWithItem(['order_no' => 'ORD-PHONE', 'customer_phone' => $stored]);

        $this->getJson('/api/v1/orders/ORD-PHONE', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.customer.phone', $stored)
            ->assertJsonPath('data.customer.phone_international', $expected);
    }

    public function test_course_group_item_returns_arabic_course_and_group_titles(): void
    {
        [$course] = $this->courses(1);
        $group = $this->group($course, 'دفعة أكتوبر', '2026-11-02');
        $order = Order::factory()->create(['order_no' => 'ORD-GROUP']);
        $this->item($order, CourseGroup::class, $group->id);

        $this->getJson('/api/v1/orders/ORD-GROUP/items', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.is_bundle', false)
            ->assertJsonPath('data.0.type', 'course_group')
            ->assertJsonMissingPath('data.0.bundle_title')
            ->assertJsonPath('data.0.courses', [[
                'course_title' => 'الدورة 1',
                'group_title' => 'دفعة أكتوبر',
                'group_start_date' => '2026-11-02',
            ]]);
    }

    public function test_bundle_item_returns_each_selected_group(): void
    {
        [$courseA, $courseB] = $this->courses(2);
        $groupA = $this->group($courseA, 'دفعة أكتوبر', '2026-11-02');
        $groupB = $this->group($courseB, 'دفعة نوفمبر', '2026-11-20');
        $bundle = $this->bundle([$courseA, $courseB]);
        $order = Order::factory()->create(['order_no' => 'ORD-BUNDLE']);
        $this->item($order, Bundle::class, $bundle->id, ['selected_course_group_ids' => [$groupA->id, $groupB->id]]);

        $this->getJson('/api/v1/orders/ORD-BUNDLE', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.items.0.is_bundle', true)
            ->assertJsonPath('data.items.0.type', 'bundle')
            ->assertJsonPath('data.items.0.bundle_title', 'باقة الإدارة')
            ->assertJsonPath('data.items.0.courses', [
                ['course_title' => 'الدورة 1', 'group_title' => 'دفعة أكتوبر', 'group_start_date' => '2026-11-02'],
                ['course_title' => 'الدورة 2', 'group_title' => 'دفعة نوفمبر', 'group_start_date' => '2026-11-20'],
            ]);
    }

    public function test_bundle_item_without_saved_selection_lists_bundle_courses(): void
    {
        [$courseA, $courseB] = $this->courses(2);
        $bundle = $this->bundle([$courseA, $courseB]);
        $order = Order::factory()->create(['order_no' => 'ORD-OLDBUNDLE']);
        $this->item($order, Bundle::class, $bundle->id);

        $this->getJson('/api/v1/orders/ORD-OLDBUNDLE/items', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data.0.courses')
            ->assertJsonPath('data.0.courses.0.group_title', null)
            ->assertJsonPath('data.0.courses.0.group_start_date', null);
    }

    public function test_titles_are_arabic_only_without_english_fallback(): void
    {
        $course = Course::query()->create([
            'category_id' => $this->category()->id,
            'title' => ['en' => 'English only'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'english-only',
            'status' => 'published',
        ]);
        $group = $this->group($course, 'دفعة', '2026-11-02');
        $order = Order::factory()->create(['order_no' => 'ORD-EN']);
        $this->item($order, CourseGroup::class, $group->id);

        $this->getJson('/api/v1/orders/ORD-EN/items', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.courses.0.course_title', null);
    }

    public function test_listing_orders_does_not_run_a_query_per_item(): void
    {
        [$courseA, $courseB] = $this->courses(2);
        $groupA = $this->group($courseA, 'دفعة 1', '2026-11-02');
        $groupB = $this->group($courseB, 'دفعة 2', '2026-11-20');
        $bundle = $this->bundle([$courseA, $courseB]);

        foreach (range(1, 5) as $i) {
            $order = Order::factory()->create(['order_no' => 'ORD-N'.$i]);
            $this->item($order, CourseGroup::class, $groupA->id);
            $this->item($order, Bundle::class, $bundle->id, ['selected_course_group_ids' => [$groupA->id, $groupB->id]]);
        }

        DB::enableQueryLog();
        $this->getJson('/api/v1/orders', $this->headers())->assertOk()->assertJsonCount(5, 'data');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(25, $queryCount);
    }

    private function category(): Category
    {
        return Category::query()->firstOrCreate(['slug' => 'api-category'], [
            'name' => ['en' => 'Category'],
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, Course>
     */
    private function courses(int $count): array
    {
        return collect(range(1, $count))->map(fn (int $i): Course => Course::query()->create([
            'category_id' => $this->category()->id,
            'title' => ['ar' => 'الدورة '.$i, 'en' => 'Course '.$i],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'api-course-'.$i,
            'status' => 'published',
        ]))->all();
    }

    private function group(Course $course, string $arabicTitle, string $startDate): CourseGroup
    {
        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['ar' => $arabicTitle, 'en' => 'Group'],
            'group_price_amount' => 1500,
            'status' => 'open',
            'start_at' => $startDate.' 09:00:00',
        ]);
    }

    /**
     * @param  array<int, Course>  $courses
     */
    private function bundle(array $courses): Bundle
    {
        $bundle = Bundle::query()->create([
            'category_id' => $this->category()->id,
            'title' => ['ar' => 'باقة الإدارة', 'en' => 'Management Bundle'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'api-bundle',
            'price' => 3000,
            'is_active' => true,
        ]);
        $bundle->courses()->sync(collect($courses)->pluck('id')->all());

        return $bundle;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function item(Order $order, string $purchasableType, int $purchasableId, array $metadata = []): OrderItem
    {
        return OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $purchasableId,
            'title_snapshot' => 'Snapshot title',
            'currency' => 'SAR',
            'price_amount' => 1500,
            'quantity' => 1,
            'metadata_snapshot' => $metadata,
        ]);
    }

    public function test_it_filters_by_status(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-PAID', 'status' => OrderStatus::Paid]);
        $this->orderWithItem(['order_no' => 'ORD-PENDING', 'status' => OrderStatus::Pending]);

        $this->getJson('/api/v1/orders?status=paid', $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_no', 'ORD-PAID');
    }

    public function test_it_filters_by_created_date_range(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-OLD'])->forceFill(['created_at' => '2026-01-10 10:00:00'])->save();
        $this->orderWithItem(['order_no' => 'ORD-MID'])->forceFill(['created_at' => '2026-02-15 10:00:00'])->save();
        $this->orderWithItem(['order_no' => 'ORD-NEW'])->forceFill(['created_at' => '2026-03-20 10:00:00'])->save();

        $this->getJson('/api/v1/orders?from=2026-02-01&to=2026-02-28', $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_no', 'ORD-MID');
    }

    public function test_it_filters_by_updated_since(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-STALE'])->forceFill(['updated_at' => '2026-01-01 00:00:00'])->saveQuietly();
        $this->orderWithItem(['order_no' => 'ORD-FRESH'])->forceFill(['updated_at' => '2026-06-01 00:00:00'])->saveQuietly();

        $this->getJson('/api/v1/orders?updated_since=2026-05-01T00:00:00Z', $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_no', 'ORD-FRESH');
    }

    public function test_it_paginates_and_caps_per_page(): void
    {
        foreach (range(1, 3) as $i) {
            $this->orderWithItem(['order_no' => 'ORD-PAGE-'.$i]);
        }

        $this->getJson('/api/v1/orders?per_page=2', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/v1/orders?per_page=101', $this->headers())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $this->getJson('/api/v1/orders?status=unknown&from=10-02-2026', $this->headers())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'from']);
    }

    public function test_it_shows_an_order_by_order_number(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-SHOW']);

        $this->getJson('/api/v1/orders/ORD-SHOW', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.order_no', 'ORD-SHOW')
            ->assertJsonCount(1, 'data.items');
    }

    public function test_unknown_order_number_returns_json_404(): void
    {
        $this->get('/api/v1/orders/ORD-NOPE', $this->headers())
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_it_lists_items_of_an_order(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-ITEMS']);

        $this->getJson('/api/v1/orders/ORD-ITEMS/items', $this->headers())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Bootcamp');
    }

    public function test_soft_deleted_orders_are_not_exposed(): void
    {
        $this->orderWithItem(['order_no' => 'ORD-DELETED'])->delete();

        $this->getJson('/api/v1/orders', $this->headers())->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/orders/ORD-DELETED', $this->headers())->assertNotFound();
    }
}
