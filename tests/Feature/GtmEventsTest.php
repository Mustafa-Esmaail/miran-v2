<?php

namespace Tests\Feature;

use App\Livewire\Cart\CartPanel;
use App\Livewire\Courses\CourseShow;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\OrderItem;
use App\Support\Gtm\GtmEventQueue;
use App\Support\Gtm\GtmItemFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GtmEventsTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(string $title, int $price): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $title],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
            'price' => $price,
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $title.' Group'],
            'status' => 'open',
            'group_price_amount' => $price,
        ]);
    }

    public function test_adding_a_course_group_queues_add_to_cart_keyed_by_course_id(): void
    {
        $group = $this->makeGroup('Course A', 1000);

        Livewire::test(CourseShow::class, ['slug' => $group->course->slug])
            ->set('courseGroupId', (string) $group->id)
            ->set('quantity', 2)
            ->call('addCourseToCart');

        $queued = GtmEventQueue::pull();

        $this->assertCount(1, $queued);
        $this->assertSame('add_to_cart', $queued[0]['event']);
        $this->assertSame('SAR', $queued[0]['data']['currency']);
        $this->assertSame(2000, $queued[0]['data']['value']);
        $this->assertSame('course_'.$group->course_id, $queued[0]['data']['items'][0]['item_id']);
        $this->assertSame(1000, $queued[0]['data']['items'][0]['price']);
        $this->assertSame(2, $queued[0]['data']['items'][0]['quantity']);
        $this->assertSame([], GtmEventQueue::pull());
    }

    public function test_queued_events_are_rendered_once_on_the_next_full_page(): void
    {
        GtmEventQueue::push('add_to_cart', ['currency' => 'SAR', 'value' => 1000, 'items' => []]);

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('window.trackEvent("add_to_cart"', false);

        $this->get(route('cart'))
            ->assertOk()
            ->assertDontSee('window.trackEvent("add_to_cart"', false);
    }

    public function test_course_page_dispatches_view_item(): void
    {
        $group = $this->makeGroup('Course A', 1000);

        Livewire::test(CourseShow::class, ['slug' => $group->course->slug])
            ->assertDispatched('gtm-event', function (string $event, array $params) use ($group): bool {
                return $params['name'] === 'view_item'
                    && $params['data']['currency'] === 'SAR'
                    && $params['data']['value'] === 1000
                    && $params['data']['items'][0]['item_id'] === 'course_'.$group->course_id;
            });
    }

    public function test_cart_page_dispatches_view_cart_only_when_cart_has_items(): void
    {
        Livewire::test(CartPanel::class)->assertNotDispatched('gtm-event');

        $group = $this->makeGroup('Course A', 1000);
        Livewire::test(CourseShow::class, ['slug' => $group->course->slug])
            ->set('courseGroupId', (string) $group->id)
            ->call('addCourseToCart');

        Livewire::test(CartPanel::class)
            ->assertDispatched('gtm-event', function (string $event, array $params) use ($group): bool {
                return $params['name'] === 'view_cart'
                    && $params['data']['items'][0]['item_id'] === 'course_'.$group->course_id;
            });
    }

    public function test_order_items_use_the_same_item_ids_as_cart_events(): void
    {
        $courseItem = new OrderItem([
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => 99,
            'title_snapshot' => 'Course A',
            'price_amount' => 500,
            'quantity' => 1,
            'metadata_snapshot' => ['type_label' => 'course_group', 'course_id' => 7],
        ]);
        $bundleItem = new OrderItem([
            'purchasable_type' => Bundle::class,
            'purchasable_id' => 3,
            'title_snapshot' => 'Bundle',
            'price_amount' => 900,
            'quantity' => 1,
            'metadata_snapshot' => ['type_label' => 'bundle', 'bundle_id' => 3],
        ]);

        $this->assertSame('course_7', GtmItemFormatter::fromOrderItem($courseItem)['item_id']);
        $this->assertSame('course', GtmItemFormatter::fromOrderItem($courseItem)['item_category']);
        $this->assertSame('bundle_3', GtmItemFormatter::fromOrderItem($bundleItem)['item_id']);
    }
}
