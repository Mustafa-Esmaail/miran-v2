<?php

namespace Tests\Feature;

use App\Enums\CourseTemplate;
use App\Livewire\Courses\CourseShow;
use App\Models\Course;
use Database\Seeders\BootcampSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BootcampSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_bootcamp_for_each_design_and_can_run_again(): void
    {
        $this->seed(BootcampSeeder::class);
        $this->seed(BootcampSeeder::class);

        $expected = [
            'ai-bootcamp' => [CourseTemplate::Bootcamp, 'livewire.courses.course-bootcamp'],
            'ai-bootcamp-v2' => [CourseTemplate::BootcampV2, 'livewire.courses.course-bootcamp-v2'],
        ];

        $this->assertSame(2, Course::query()->whereIn('slug', array_keys($expected))->count());

        foreach ($expected as $slug => [$template, $view]) {
            $course = Course::query()->where('slug', $slug)->firstOrFail();
            $group = $course->groups()->sole();

            $this->assertSame($template, $course->template);
            $this->assertSame(1450.0, (float) $course->price);
            $this->assertNull($group->group_price_amount);
            $this->assertCount(8, $course->curriculumSections);
            $this->assertCount(9, $group->sessions);
            $this->assertTrue($course->curriculumSections()->where('is_capstone', true)->exists());

            app()->setLocale('en');

            Livewire::test(CourseShow::class, ['slug' => $slug])
                ->assertViewIs($view)
                ->assertSee('Professional prompting')
                ->assertSee('Do I need technical or programming experience?')
                ->assertSee('Next cohort');
        }
    }
}
