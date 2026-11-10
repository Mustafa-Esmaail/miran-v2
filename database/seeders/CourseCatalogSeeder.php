<?php

namespace Database\Seeders;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\DeliveryType;
use App\Enums\LessonType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CourseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'leadership' => [
                'ar' => 'القيادة والإدارة',
                'en' => 'Leadership & Management',
            ],
            'technology' => [
                'ar' => 'التقنية والتحول الرقمي',
                'en' => 'Technology & Digital Transformation',
            ],
            'marketing' => [
                'ar' => 'التسويق والمبيعات',
                'en' => 'Marketing & Sales',
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $slug => $name) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['ar' => $name['ar'], 'en' => $name['en']],
                    'is_active' => true,
                ]
            );

            $categoryIds[$slug] = $category->id;
        }

        $courses = [
            ['slug' => 'strategic-leadership', 'category' => 'leadership', 'title_ar' => 'القيادة الاستراتيجية', 'title_en' => 'Strategic Leadership', 'price' => 1299.00, 'group_price_amount' => 1299, 'delivery_type' => DeliveryType::Hybrid, 'course_level' => CourseLevel::Intermediate],
            ['slug' => 'team-performance', 'category' => 'leadership', 'title_ar' => 'إدارة أداء الفرق', 'title_en' => 'Team Performance Management', 'price' => 999.00, 'group_price_amount' => 999, 'delivery_type' => DeliveryType::Online, 'course_level' => CourseLevel::Beginner],
            ['slug' => 'data-driven-decisions', 'category' => 'technology', 'title_ar' => 'القرارات المبنية على البيانات', 'title_en' => 'Data-Driven Decisions', 'price' => 1499.00, 'group_price_amount' => 1499, 'delivery_type' => DeliveryType::RemoteLive, 'course_level' => CourseLevel::Advanced],
            ['slug' => 'ai-for-managers', 'category' => 'technology', 'title_ar' => 'الذكاء الاصطناعي للمديرين', 'title_en' => 'AI for Managers', 'price' => 1599.00, 'group_price_amount' => 1599, 'delivery_type' => DeliveryType::Online, 'course_level' => CourseLevel::Intermediate],
            ['slug' => 'product-management-foundations', 'category' => 'technology', 'title_ar' => 'أساسيات إدارة المنتجات', 'title_en' => 'Product Management Foundations', 'price' => 1199.00, 'group_price_amount' => 1199, 'delivery_type' => DeliveryType::Hybrid, 'course_level' => CourseLevel::Beginner],
            ['slug' => 'b2b-sales-excellence', 'category' => 'marketing', 'title_ar' => 'تميّز مبيعات B2B', 'title_en' => 'B2B Sales Excellence', 'price' => 1099.00, 'group_price_amount' => 1099, 'delivery_type' => DeliveryType::Offline, 'course_level' => CourseLevel::Intermediate],
            ['slug' => 'digital-marketing-accelerator', 'category' => 'marketing', 'title_ar' => 'تسريع التسويق الرقمي', 'title_en' => 'Digital Marketing Accelerator', 'price' => 999.00, 'group_price_amount' => 999, 'delivery_type' => DeliveryType::Online, 'course_level' => CourseLevel::Beginner],
            ['slug' => 'customer-success-playbook', 'category' => 'marketing', 'title_ar' => 'دليل نجاح العملاء', 'title_en' => 'Customer Success Playbook', 'price' => 899.00, 'group_price_amount' => 899, 'delivery_type' => DeliveryType::RemoteLive, 'course_level' => CourseLevel::Beginner],
            ['slug' => 'operational-excellence', 'category' => 'leadership', 'title_ar' => 'التميّز التشغيلي', 'title_en' => 'Operational Excellence', 'price' => 1399.00, 'group_price_amount' => 1399, 'delivery_type' => DeliveryType::Hybrid, 'course_level' => CourseLevel::Advanced],
            ['slug' => 'innovation-workshop', 'category' => 'technology', 'title_ar' => 'ورشة الابتكار', 'title_en' => 'Innovation Workshop', 'price' => 1299.00, 'group_price_amount' => 1299, 'delivery_type' => DeliveryType::Offline, 'course_level' => CourseLevel::Intermediate],
        ];

        foreach ($courses as $index => $data) {
            $daysCount = 4;

            $course = Course::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'category_id' => $categoryIds[$data['category']],
                    'title' => ['ar' => $data['title_ar'], 'en' => $data['title_en']],
                    'summary' => [
                        'ar' => 'ملخص عملي للدورة: '.$data['title_ar'],
                        'en' => 'Practical summary for '.$data['title_en'],
                    ],
                    'description' => [
                        'ar' => 'وصف تفصيلي للدورة '.$data['title_ar'].' مع أمثلة تطبيقية وتمارين عملية.',
                        'en' => 'Detailed description for '.$data['title_en'].' with practical examples and exercises.',
                    ],
                    'price' => $data['price'],
                    'status' => CourseStatus::Published,
                    'delivery_type' => $data['delivery_type'],
                    'course_level' => $data['course_level'],
                    'duration_hours' => 12 + $index,
                    'duration_group' => '4 weeks',
                    'days_count' => $daysCount,
                    'time_from' => '18:00',
                    'time_to' => '21:00',
                    'sku' => 'CRS-'.Str::upper(Str::replace('-', '', $data['slug'])),
                ]
            );

            $startAt = Carbon::create(2026, 3, 1, 18, 0, 0)->addDays($index * 3);
            $endAt = (clone $startAt)->addWeeks(4);

            $courseGroup = CourseGroup::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'start_at' => $startAt,
                ],
                [
                    'title' => [
                        'ar' => 'مجموعة '.$data['title_ar'],
                        'en' => $data['title_en'].' Group',
                    ],
                    'group_price_amount' => $data['group_price_amount'],
                    'end_at' => $endAt,
                    'capacity' => 30,
                    'enrollment_deadline_at' => (clone $startAt)->subDays(2),
                    'status' => CourseGroupStatus::Open,
                ]
            );

            $days = collect(range(1, $daysCount))->map(function (int $dayNumber) use ($course) {
                return CourseCurriculumSection::query()->updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'sort_order' => $dayNumber,
                    ],
                    [
                        'title' => [
                            'ar' => 'اليوم '.$dayNumber,
                            'en' => 'Day '.$dayNumber,
                        ],
                        'description' => [
                            'ar' => 'محتوى وأنشطة اليوم '.$dayNumber,
                            'en' => 'Content and activities for day '.$dayNumber,
                        ],
                    ]
                );
            });

            $lessonBlueprints = [
                [
                    'sort_order' => 1,
                    'section_sort_order' => 1,
                    'title' => ['ar' => 'مقدمة الدورة', 'en' => 'Course Introduction'],
                    'content' => [
                        'ar' => 'محتوى تمهيدي يوضح أهداف الدورة وخطة التعلم.',
                        'en' => 'Introductory content covering objectives and the learning roadmap.',
                    ],
                    'type' => LessonType::Text,
                    'is_preview' => true,
                    'video_url' => null,
                ],
                [
                    'sort_order' => 2,
                    'section_sort_order' => 2,
                    'title' => ['ar' => 'درس فيديو تطبيقي', 'en' => 'Practical Video Lesson'],
                    'content' => [
                        'ar' => 'شرح مرئي مع سيناريوهات تطبيقية.',
                        'en' => 'Visual explanation with practical scenarios.',
                    ],
                    'type' => LessonType::Video,
                    'is_preview' => false,
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                ],
                [
                    'sort_order' => 3,
                    'section_sort_order' => 3,
                    'title' => ['ar' => 'خلاصة وتمارين', 'en' => 'Wrap-up and Exercises'],
                    'content' => [
                        'ar' => 'تمارين ختامية لقياس الاستيعاب وتطبيق المهارات.',
                        'en' => 'Final exercises to evaluate understanding and apply skills.',
                    ],
                    'type' => LessonType::Text,
                    'is_preview' => false,
                    'video_url' => null,
                ],
            ];

            foreach ($lessonBlueprints as $lessonData) {
                $section = $days->firstWhere('sort_order', $lessonData['section_sort_order']);

                Lesson::query()->updateOrCreate(
                    ['course_id' => $course->id, 'sort_order' => $lessonData['sort_order']],
                    [
                        'course_curriculum_section_id' => $section?->id,
                        'title' => $lessonData['title'],
                        'content' => $lessonData['content'],
                        'type' => $lessonData['type'],
                        'video_url' => $lessonData['video_url'],
                        'is_preview' => $lessonData['is_preview'],
                    ]
                );
            }

            foreach (range(1, min($daysCount, 2)) as $sessionDay) {
                $section = $days->firstWhere('sort_order', $sessionDay);

                CourseGroupSession::query()->updateOrCreate(
                    [
                        'course_group_id' => $courseGroup->id,
                        'title' => 'Live Session '.$sessionDay,
                    ],
                    [
                        'course_curriculum_section_id' => $section?->id,
                        'content' => '<p>Interactive live session for day '.$sessionDay.'.</p>',
                        'start_at' => (clone $startAt)->addDays($sessionDay - 1),
                        'duration_minutes' => 120,
                        'join_url' => 'https://example.com/live-session-'.$sessionDay,
                        'recording_url' => null,
                        'attachments' => ['course-intros/session-'.$sessionDay.'-slides.pdf'],
                    ]
                );
            }

            foreach (range(1, min($daysCount, 2)) as $attachmentDay) {
                $section = $days->firstWhere('sort_order', $attachmentDay);

                CourseAttachment::query()->updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'course_curriculum_section_id' => $section?->id,
                        'file_path' => 'course-intros/day-'.$attachmentDay.'-workbook.pdf',
                    ],
                    [
                        'title' => [
                            'ar' => 'ملف العمل - اليوم '.$attachmentDay,
                            'en' => 'Day '.$attachmentDay.' Workbook',
                        ],
                        'visibility' => 'enrolled_only',
                    ]
                );
            }
        }
    }
}
