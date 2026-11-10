<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\GoogleSheetsService;
use App\Services\OrderApprovalService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderGoogleSheetSyncTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderWithItem(): Order
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'sheet-sync-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['ar' => 'دورة'],
            'summary' => ['ar' => 'ملخص'],
            'description' => ['ar' => 'وصف'],
            'slug' => 'sheet-sync-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['ar' => 'مجموعة'],
            'group_price_amount' => 1000,
            'status' => 'open',
            'start_at' => '2026-08-01 10:00:00',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Jane Doe',
            'customer_phone' => '0501234567',
            'order_no' => 'ORD-SHEET-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'مجموعة',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        return $order->fresh();
    }

    public function test_build_rows_for_order_produces_expected_columns(): void
    {
        $order = $this->makeOrderWithItem();

        $rows = app(GoogleSheetsService::class)->buildRowsForOrder($order);

        $this->assertCount(1, $rows);
        $row = $rows[0];

        $this->assertSame($order->created_at->format('Y-m-d H:i'), $row[0]); // A
        $this->assertSame('0501234567', $row[1]); // B
        $this->assertSame('دورة - مجموعة', $row[2]); // C — course title + group title
        $this->assertSame('دورة - مجموعة', $row[3]); // D — duplicate of C
        $this->assertSame('', $row[4]); // E — intentionally left blank
        $this->assertSame('2026-08-01', $row[5]); // F
        $this->assertSame('Jane Doe', $row[6]); // G
        $this->assertSame('ORD-SHEET-1', $row[7]); // H
        $this->assertSame('1000', $row[9]); // J
        $this->assertSame('', $row[10]); // K — employee code (none referred in this fixture)
        // L..T (indices 11-19) must be blank
        for ($i = 11; $i <= 19; $i++) {
            $this->assertSame('', $row[$i], "column index {$i} should be blank");
        }
        $this->assertSame('Jane', $row[20]); // U
        $this->assertSame('Doe', $row[21]); // V
        $this->assertSame($order->user->email, $row[22]); // W
        $this->assertCount(24, $row); // A..X
    }

    public function test_multi_item_order_reports_each_items_own_price_not_the_order_total(): void
    {
        $order = $this->makeOrderWithItem();

        $category = Category::query()->create([
            'name' => ['en' => 'Category 2'],
            'slug' => 'sheet-sync-category-2',
            'is_active' => true,
        ]);

        $course2 = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['ar' => 'دورة 2'],
            'summary' => ['ar' => 'ملخص'],
            'description' => ['ar' => 'وصف'],
            'slug' => 'sheet-sync-course-2',
            'status' => 'published',
        ]);

        $group2 = CourseGroup::query()->create([
            'course_id' => $course2->id,
            'title' => ['ar' => 'مجموعة 2'],
            'group_price_amount' => 500,
            'status' => 'open',
            'start_at' => '2026-09-01 10:00:00',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group2->id,
            'title_snapshot' => 'مجموعة 2',
            'price_amount' => 500,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $rows = app(GoogleSheetsService::class)->buildRowsForOrder($order->fresh());

        $this->assertCount(2, $rows);
        $this->assertSame('1000', $rows[0][9]); // J — first item's own price, not the 1500 order total
        $this->assertSame('500', $rows[1][9]); // J — second item's own price
    }

    public function test_bank_transfer_order_reports_payment_method_even_without_a_payment_row(): void
    {
        $order = $this->makeOrderWithItem();

        \App\Models\BankTransferReceipt::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'receipt_path' => 'bank-receipts/test.pdf',
            'status' => 'submitted',
        ]);

        $rows = app(GoogleSheetsService::class)->buildRowsForOrder($order->fresh());

        $this->assertSame('bank_transfer', $rows[0][8]); // I
    }

    public function test_approval_skips_sync_when_disabled(): void
    {
        $order = $this->makeOrderWithItem();

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldNotReceive('appendRow');
        $this->app->instance(GoogleSheetsService::class, $sheets);

        app(OrderApprovalService::class)->approve($order->fresh());

        $this->assertNull($order->fresh()->sheet_synced_at);
    }

    public function test_approval_syncs_when_enabled_and_marks_synced_at(): void
    {
        $order = $this->makeOrderWithItem();

        $settings = app(SettingsService::class);
        $settings->updateFromForm([
            'orders_sync_to_sheets' => true,
            'orders_sheet_id' => 'test-sheet-id',
        ]);

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('buildRowsForOrder')->once()->andReturn([['a']]);
        $sheets->shouldReceive('appendRow')->once()->with('test-sheet-id', ['a'])->andReturn(true);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        app(OrderApprovalService::class)->approve($order->fresh());

        $this->assertNotNull($order->fresh()->sheet_synced_at);
    }

    public function test_approval_does_not_mark_synced_when_append_fails(): void
    {
        $order = $this->makeOrderWithItem();

        $settings = app(SettingsService::class);
        $settings->updateFromForm([
            'orders_sync_to_sheets' => true,
            'orders_sheet_id' => 'test-sheet-id',
        ]);

        $sheets = Mockery::mock(GoogleSheetsService::class);
        $sheets->shouldReceive('buildRowsForOrder')->once()->andReturn([['a']]);
        $sheets->shouldReceive('appendRow')->once()->andReturn(false);
        $this->app->instance(GoogleSheetsService::class, $sheets);

        app(OrderApprovalService::class)->approve($order->fresh());

        $this->assertNull($order->fresh()->sheet_synced_at);
    }
}
