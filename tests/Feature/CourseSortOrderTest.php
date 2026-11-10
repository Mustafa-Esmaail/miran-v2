<?php

namespace Tests\Feature;

use App\Livewire\Courses\CoursesIndex;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseSortOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_sort_order_defaults_to_one_hundred_when_omitted(): void
    {
        $course = $this->makeCourse('course-a', 'A');

        $this->assertSame(100, $course->fresh()->sort_order);
    }

    public function test_courses_index_orders_by_sort_order_for_the_default_sort(): void
    {
        $first = $this->makeCourse('course-first', 'First', sortOrder: 102);
        $second = $this->makeCourse('course-second', 'Second', sortOrder: 100);
        $third = $this->makeCourse('course-third', 'Third', sortOrder: 101);

        Livewire::test(CoursesIndex::class)
            ->assertSeeInOrder([
                $second->getTranslation('title', 'ar'),
                $third->getTranslation('title', 'ar'),
                $first->getTranslation('title', 'ar'),
            ]);
    }

    private function makeCourse(string $slug, string $title, ?int $sortOrder = null): Course
    {
        $category = Category::firstOrCreate(
            ['slug' => 'sort-order-tests'],
            ['name' => ['en' => 'Category', 'ar' => 'Category'], 'is_active' => true]
        );

        return Course::create(array_filter([
            'category_id' => $category->id,
            'title' => ['en' => $title, 'ar' => $title],
            'summary' => ['en' => 'Summary', 'ar' => 'Summary'],
            'description' => ['en' => 'Description', 'ar' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
            'sort_order' => $sortOrder,
        ], fn ($value) => $value !== null));
    }
}
