<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Livewire\Courses\Questions;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CourseQuestionsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_questions_component_allows_guests(): void
    {
        $course = $this->createCourse();

        Livewire::test(Questions::class, ['courseId' => $course->id])
            ->assertStatus(200);
    }

    public function test_questions_component_allows_authenticated_users(): void
    {
        $user = \App\Models\User::factory()->create();
        $course = $this->createCourse();

        Livewire::actingAs($user)
            ->test(Questions::class, ['courseId' => $course->id])
            ->assertStatus(200);
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => Str::slug(Str::random(12)),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => Str::slug(Str::random(12)),
            'status' => CourseStatus::Published,
        ]);
    }
}
