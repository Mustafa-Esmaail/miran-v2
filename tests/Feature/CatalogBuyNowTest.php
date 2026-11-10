<?php

namespace Tests\Feature;

use App\Livewire\Courses\CourseShow;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogBuyNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_show_buy_now_adds_selected_group_to_cart_and_redirects_to_cart(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
            'price' => 1200,
        ]);
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'status' => 'open',
            'group_price_amount' => 1200,
        ]);

        Livewire::actingAs($user)
            ->test(CourseShow::class, ['slug' => $course->slug])
            ->set('courseGroupId', (string) $group->id)
            ->call('buyNow')
            ->assertRedirect(route('cart'));

        $this->assertDatabaseHas('cart_items', [
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
        ]);
    }

    public function test_course_show_renders_buy_now_and_add_to_cart_buttons(): void
    {
        $courseShow = file_get_contents(base_path('resources/views/livewire/courses/course-show.blade.php'));
        $courseCard = file_get_contents(base_path('resources/views/components/course-card.blade.php'));
        $bundleCard = file_get_contents(base_path('resources/views/components/bundle-card.blade.php'));

        $this->assertNotFalse($courseShow);
        $this->assertNotFalse($courseCard);
        $this->assertNotFalse($bundleCard);
        $this->assertStringContainsString('wire:click="buyNow"', $courseShow);
        $this->assertStringContainsString("__('web.course.buy_now')", $courseShow);
        $this->assertStringContainsString("__('web.course.add_to_cart')", $courseShow);
        $this->assertStringContainsString('hover:bg-[#86634e]', $courseShow);
        $this->assertStringContainsString('hover:text-white', $courseShow);
        $this->assertStringNotContainsString('buyNow(@js(\\App\\Models\\Course::class)', $courseCard);
        $this->assertStringNotContainsString('buyNow(@js(\\App\\Models\\Bundle::class)', $bundleCard);
    }
}
