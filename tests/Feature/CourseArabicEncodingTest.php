<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseArabicEncodingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_and_reads_arabic_translations_for_course_content(): void
    {
        $category = Category::query()->create([
            'name' => ['ar' => 'دورات تقنية', 'en' => 'Tech Courses'],
            'slug' => 'tech-courses',
            'is_active' => true,
        ]);

        $summaryAr = 'ملخص باللغة العربية';
        $descriptionAr = 'وصف تفصيلي باللغة العربية.';

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['ar' => 'دورة لارافيل', 'en' => 'Laravel Course'],
            'summary' => ['ar' => $summaryAr, 'en' => 'English summary'],
            'description' => ['ar' => $descriptionAr, 'en' => 'English description'],
            'slug' => 'laravel-course',
            'status' => 'draft',
        ]);

        $stored = Course::query()->findOrFail($course->id);

        $this->assertSame($summaryAr, $stored->getTranslation('summary', 'ar'));
        $this->assertSame($descriptionAr, $stored->getTranslation('description', 'ar'));
    }
}
