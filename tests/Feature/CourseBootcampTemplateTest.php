<?php

namespace Tests\Feature;

use App\Enums\CourseTemplate;
use App\Filament\Admin\Resources\Courses\Schemas\CourseBootcampContentForm;
use App\Livewire\Courses\CourseShow;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\ViewModels\CourseBootcampViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CourseBootcampTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_defaults_to_the_standard_template(): void
    {
        $course = $this->createCourse();

        $this->assertSame(CourseTemplate::Course, $course->fresh()->template);

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-show');
    }

    public function test_bootcamp_course_renders_the_bootcamp_view_with_its_content(): void
    {
        $course = $this->createCourse([
            'template' => CourseTemplate::Bootcamp,
            'template_content' => [
                'tagline' => ['ar' => 'معسكر تطبيقي', 'en' => 'Applied bootcamp'],
                'pillars' => [
                    ['title' => ['ar' => 'تأسّس', 'en' => 'Foundations'], 'description' => ['ar' => 'وصف', 'en' => 'Description']],
                ],
                'faqs' => [
                    ['question' => ['ar' => 'أحتاج خبرة؟', 'en' => 'Do I need experience?'], 'answer' => ['ar' => 'لا', 'en' => 'No, we start from scratch.']],
                ],
            ],
        ]);
        $group = $this->createGroup($course, ['start_at' => now()->addWeek()]);
        $module = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['ar' => 'التوجيه الاحترافي', 'en' => 'Professional prompting'],
            'sort_order' => 1,
            'week' => 1,
            'badge' => ['ar' => 'قوالب', 'en' => 'Templates'],
            'outcome' => ['ar' => 'قوالب جاهزة', 'en' => 'Ready-made templates'],
            'bullets' => [['text' => ['ar' => 'نقطة', 'en' => 'Turn weak prompts into strong ones']]],
            'is_featured' => true,
        ]);
        CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $module->id,
            'title' => 'Kickoff session',
            'start_at' => now()->addWeek()->setTime(18, 0),
            'duration_minutes' => 240,
        ]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-bootcamp')
            ->assertSee('Foundations')
            ->assertSee('Do I need experience?')
            ->assertSee('Professional prompting')
            ->assertSee('Templates')
            ->assertSee('Ready-made templates')
            ->assertSee('Turn weak prompts into strong ones')
            ->assertSee('Kickoff session')
            ->assertSee(__('web.bootcamp.book_seat_now'))
            ->assertSee(__('web.course.add_to_cart'))
            ->assertSee(__('web.course.select_group'))
            ->assertSee('wire:model="courseGroupId"', false)
            ->assertSee('wire:model.live="quantity"', false)
            ->assertSee('id="book"', false)
            ->assertSee('href="#book"', false)
            ->assertDontSee('href="#pricing"', false)
            ->assertDontSee('bookBootcampSeat', false);
    }

    public function test_bootcamp_page_hides_sections_without_content(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::Bootcamp]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-bootcamp')
            ->assertDontSee(__('web.bootcamp.faq.title'))
            ->assertDontSee(__('web.bootcamp.method.title'))
            ->assertSee(__('web.bootcamp.registration_closed'));
    }

    public function test_admin_can_choose_bootcamp_design_two(): void
    {
        $this->assertArrayHasKey(CourseTemplate::BootcampV2->value, CourseTemplate::options());
        $this->assertTrue(CourseTemplate::Bootcamp->isBootcamp());
        $this->assertTrue(CourseTemplate::BootcampV2->isBootcamp());
        $this->assertFalse(CourseTemplate::Course->isBootcamp());
        $this->assertTrue(CourseBootcampContentForm::isBootcamp('bootcamp_v2'));
        $this->assertFalse(CourseBootcampContentForm::isBootcamp('course'));
    }

    public function test_bootcamp_design_two_renders_its_view_with_the_shared_content(): void
    {
        $course = $this->createCourse([
            'template' => CourseTemplate::BootcampV2,
            'template_content' => [
                'pillars' => [
                    ['title' => ['ar' => 'تأسّس', 'en' => 'Foundations'], 'description' => ['ar' => 'وصف', 'en' => 'Understand how AI thinks']],
                ],
                'method_steps' => [
                    ['title' => ['ar' => 'تدريب مباشر', 'en' => 'Live training']],
                    ['title' => ['ar' => 'تطبيق', 'en' => 'Apply it at work']],
                ],
                'method_note' => ['ar' => 'فقرة', 'en' => "You practice between sessions.\nEvery session is recorded."],
                'audience' => [
                    ['title' => ['ar' => 'موظف', 'en' => 'Employee'], 'description' => ['ar' => 'وصف', 'en' => 'who wants to finish faster']],
                ],
                'not_for' => ['ar' => 'مبرمج', 'en' => 'You want to learn to build models.'],
                'faqs' => [
                    ['question' => ['ar' => 'أحتاج خبرة؟', 'en' => 'Do I need experience?'], 'answer' => ['ar' => 'لا', 'en' => 'No, we start from scratch.']],
                ],
                'final_cta_title' => ['ar' => 'شغلك بعد المعسكر', 'en' => 'Your work after the bootcamp'],
            ],
        ]);
        $group = $this->createGroup($course, ['start_at' => now()->addWeek()]);
        $module = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['ar' => 'التوجيه الاحترافي', 'en' => 'Professional prompting'],
            'sort_order' => 1,
            'week' => 1,
            'outcome' => ['ar' => 'قوالب جاهزة', 'en' => 'Ready-made templates'],
            'bullets' => [['text' => ['ar' => 'نقطة', 'en' => 'Turn weak prompts into strong ones']]],
        ]);
        CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $module->id,
            'title' => 'Kickoff session',
            'start_at' => now()->addWeek()->setTime(18, 0),
            'duration_minutes' => 240,
        ]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-bootcamp-v2')
            ->assertSee('Foundations')
            ->assertSee('Understand how AI thinks')
            ->assertSee('Live training')
            ->assertSee('Every session is recorded.')
            ->assertSee('Professional prompting')
            ->assertSee('Ready-made templates')
            ->assertSee('Turn weak prompts into strong ones')
            ->assertSee('Employee')
            ->assertSee('You want to learn to build models.')
            ->assertSee('Do I need experience?')
            ->assertSee('Kickoff session')
            ->assertSee('Your work after the bootcamp')
            ->assertSee(__('web.course.add_to_cart'))
            ->assertSee('wire:model="courseGroupId"', false)
            ->assertSee('wire:model.live="quantity"', false)
            ->assertSee('id="book"', false)
            ->assertSee('href="#book"', false)
            ->assertDontSee('bookBootcampSeat', false);
    }

    public function test_bootcamp_design_two_hides_sections_without_content(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::BootcampV2]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-bootcamp-v2')
            ->assertDontSee(__('web.bootcamp.v2.pillars.title'))
            ->assertDontSee(__('web.bootcamp.v2.method.eyebrow'))
            ->assertDontSee(__('web.bootcamp.v2.curriculum.title'))
            ->assertDontSee(__('web.bootcamp.v2.faq.title'))
            ->assertDontSee(__('web.bootcamp.v2.schedule.eyebrow'))
            ->assertSee(__('web.course.add_to_cart'))
            ->assertSee('href="#book"', false);
    }

    public function test_bootcamp_design_two_shows_related_courses(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::BootcampV2]);
        $this->createCourse([
            'category_id' => $course->category_id,
            'title' => ['en' => 'Related analytics course', 'ar' => 'دورة ذات صلة'],
        ]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertSee(__('web.course.interested_title'))
            ->assertSee('Related analytics course');
    }

    public function test_bookable_group_skips_closed_full_and_expired_groups(): void
    {
        $course = $this->createCourse();
        $closed = $this->createGroup($course, ['status' => 'closed', 'start_at' => now()->addDay()]);
        $expired = $this->createGroup($course, ['enrollment_deadline_at' => now()->subHour(), 'start_at' => now()->addDays(2)]);
        $full = $this->createGroup($course, ['capacity' => 0, 'start_at' => now()->addDays(3)]);
        $open = $this->createGroup($course, ['capacity' => 10, 'start_at' => now()->addDays(4)]);
        $later = $this->createGroup($course, ['start_at' => now()->addDays(10)]);

        $full->setAttribute('capacity', 1);
        $groups = collect([$closed, $expired, $full, $later, $open])
            ->each(fn (CourseGroup $group) => $group->setAttribute('enrollments_count', $group->is($full) ? 1 : 0));

        $this->assertTrue(CourseBootcampViewModel::bookableGroup($groups)->is($open));
    }

    public function test_bootcamp_schedule_groups_sessions_into_weeks(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::Bootcamp]);
        $group = $this->createGroup($course, ['start_at' => now()->addDay()]);
        $firstSession = now()->addDay()->setTime(18, 0);

        foreach ([0, 2, 7, 14] as $offsetDays) {
            CourseGroupSession::query()->create([
                'course_group_id' => $group->id,
                'title' => 'Session '.$offsetDays,
                'start_at' => $firstSession->copy()->addDays($offsetDays),
                'duration_minutes' => 240,
            ]);
        }

        $course->load(['groups' => fn ($query) => $query->withCount('enrollments'), 'attachments']);
        $viewModel = CourseBootcampViewModel::fromCourse($course);

        $this->assertCount(3, $viewModel->scheduleWeeks);
        $this->assertCount(2, $viewModel->scheduleWeeks[0]['sessions']);
        $this->assertSame(4, $viewModel->stats['sessions']);
        $this->assertSame(16, $viewModel->stats['hours']);
        $this->assertSame(3, $viewModel->stats['weeks']);
    }

    public function test_bootcamp_page_shows_related_courses(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::Bootcamp]);
        $this->createCourse([
            'category_id' => $course->category_id,
            'title' => ['en' => 'Related analytics course', 'ar' => 'دورة ذات صلة'],
        ]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-bootcamp')
            ->assertSee(__('web.course.interested_title'))
            ->assertSee('Related analytics course');
    }

    public function test_related_courses_fall_back_to_featured_courses_when_the_category_is_empty(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::Bootcamp]);
        $this->createCourse([
            'title' => ['en' => 'Featured design course', 'ar' => 'دورة مميزة'],
            'is_featured' => true,
        ]);
        $this->createCourse(['title' => ['en' => 'Unrelated plain course', 'ar' => 'دورة عادية']]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertSee(__('web.course.interested_title'))
            ->assertSee('Featured design course')
            ->assertDontSee('Unrelated plain course');
    }

    public function test_related_courses_prefer_the_same_category_over_featured_courses(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::Bootcamp]);
        $this->createCourse([
            'category_id' => $course->category_id,
            'title' => ['en' => 'Same category course', 'ar' => 'دورة من نفس التصنيف'],
        ]);
        $this->createCourse([
            'title' => ['en' => 'Featured design course', 'ar' => 'دورة مميزة'],
            'is_featured' => true,
        ]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertSee('Same category course')
            ->assertDontSee('Featured design course');
    }

    public function test_bootcamp_page_hides_related_courses_when_there_are_none(): void
    {
        $course = $this->createCourse(['template' => CourseTemplate::Bootcamp]);

        app()->setLocale('en');

        Livewire::test(CourseShow::class, ['slug' => $course->slug])
            ->assertViewIs('livewire.courses.course-bootcamp')
            ->assertDontSee(__('web.course.interested_title'));
    }

    private function createCourse(array $attributes = []): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        return Course::query()->create(array_merge([
            'category_id' => $category->id,
            'title' => ['en' => 'Bootcamp course', 'ar' => 'معسكر'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
            'price' => 1450,
        ], $attributes));
    }

    private function createGroup(Course $course, array $attributes = []): CourseGroup
    {
        return CourseGroup::query()->create(array_merge([
            'course_id' => $course->id,
            'title' => ['en' => 'Cohort'],
            'status' => 'open',
            'group_price_amount' => 1450,
        ], $attributes));
    }
}
