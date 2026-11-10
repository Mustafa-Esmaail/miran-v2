<?php

namespace Database\Seeders;

use App\Enums\AttachmentVisibility;
use App\Enums\CourseGroupStatus;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\DeliveryType;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Enums\QuizQuestionType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class LmsTestCourseSeeder extends Seeder
{
    private const COURSE_SLUG = 'lms-test-course';

    public function run(): void
    {
        $category = $this->seedCategory();
        $course   = $this->seedCourse($category);
        $group    = $this->seedGroup($course);
        $sections = $this->seedSections($course, $group);
        $this->seedLessons($course, $sections);
        $this->seedAttachments($course, $sections);
        $this->seedQuiz($course, $group, $sections);
        $this->seedEnrollments($course, $group);

        $this->command->info('LmsTestCourseSeeder done.');
        $this->command->table(
            ['Item', 'Detail'],
            [
                ['Course',    $course->slug],
                ['Group',     $group->getTranslation('title', 'en')],
                ['Sections',  $sections->count().' curriculum sections'],
                ['Lessons',   '5 lessons (text × 2, video, preview text, PDF)'],
                ['Quiz',      'Final knowledge check — resubmit allowed (max 3)'],
                ['Students',  'student1@example.com enrolled + any existing user with admin email'],
            ]
        );
    }

    // -------------------------------------------------------------------------

    private function seedCategory(): Category
    {
        return Category::query()->updateOrCreate(
            ['slug' => 'lms-testing'],
            [
                'name'      => ['ar' => 'اختبار النظام', 'en' => 'LMS Testing'],
                'is_active' => true,
            ]
        );
    }

    private function seedCourse(Category $category): Course
    {
        return Course::query()->updateOrCreate(
            ['slug' => self::COURSE_SLUG],
            [
                'category_id'    => $category->id,
                'title'          => [
                    'ar' => 'دورة اختبار نظام التعلم',
                    'en' => 'LMS Test Course',
                ],
                'summary'        => [
                    'ar' => 'دورة تجريبية تغطي جميع ميزات نظام إدارة التعلم.',
                    'en' => 'A sandbox course covering all LMS features: lessons, attachments and quizzes.',
                ],
                'description'    => [
                    'ar' => 'هذه الدورة مخصصة لاختبار ميزات النظام بالكامل. تشمل دروساً نصية وفيديو وملفات PDF، إضافة إلى اختبار قصير مع إمكانية إعادة التقديم.',
                    'en' => 'This course is a full-featured sandbox. It includes text, video and PDF lessons, downloadable attachments, and a short quiz with re-submission enabled so you can test every corner of the LMS.',
                ],
                'price'          => 0,
                'is_free'        => true,
                'status'         => CourseStatus::Published,
                'delivery_type'  => DeliveryType::Online,
                'course_level'   => CourseLevel::Beginner,
                'duration_hours' => 3,
                'duration_group' => '1 week',
                'days_count'     => 3,
                'sku'            => 'TST-LMS-001',
            ]
        );
    }

    private function seedGroup(Course $course): CourseGroup
    {
        $startAt = Carbon::now()->startOfDay();

        return CourseGroup::query()->updateOrCreate(
            ['course_id' => $course->id, 'status' => CourseGroupStatus::Open],
            [
                'title'                  => [
                    'ar' => 'المجموعة المفتوحة — اختبار النظام',
                    'en' => 'Open Group — LMS Test',
                ],
                'group_price_amount'     => 0,
                'start_at'               => $startAt,
                'end_at'                 => $startAt->clone()->addMonths(3),
                'capacity'               => 100,
                'enrollment_deadline_at' => $startAt->clone()->addMonths(2),
                'status'                 => CourseGroupStatus::Open,
            ]
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, CourseCurriculumSection>
     */
    private function seedSections(Course $course, CourseGroup $group): \Illuminate\Support\Collection
    {
        $definitions = [
            [
                'sort_order' => 1,
                'title' => ['ar' => 'اليوم الأول: المقدمة', 'en' => 'Day 1: Introduction'],
                'description' => ['ar' => 'مقدمة الدورة والأهداف التعليمية', 'en' => 'Course overview and learning objectives'],
            ],
            [
                'sort_order' => 2,
                'title' => ['ar' => 'اليوم الثاني: المحتوى العملي', 'en' => 'Day 2: Practical Content'],
                'description' => ['ar' => 'دروس فيديو وملفات قابلة للتنزيل', 'en' => 'Video lessons and downloadable materials'],
            ],
            [
                'sort_order' => 3,
                'title' => ['ar' => 'اليوم الثالث: التقييم', 'en' => 'Day 3: Assessment'],
                'description' => ['ar' => 'اختبار قصير مع إمكانية إعادة المحاولة', 'en' => 'Knowledge check quiz with resubmission'],
            ],
        ];

        return collect($definitions)->map(function (array $def) use ($course, $group) {
            return CourseCurriculumSection::query()->updateOrCreate(
                [
                    'course_id'       => $course->id,
                    'course_group_id' => $group->id,
                    'sort_order'      => $def['sort_order'],
                ],
                [
                    'title'       => $def['title'],
                    'description' => $def['description'],
                ]
            );
        });
    }

    /**
     * @param \Illuminate\Support\Collection<int, CourseCurriculumSection> $sections
     */
    private function seedLessons(Course $course, \Illuminate\Support\Collection $sections): void
    {
        $day1 = $sections->firstWhere('sort_order', 1);
        $day2 = $sections->firstWhere('sort_order', 2);
        $day3 = $sections->firstWhere('sort_order', 3);

        $lessons = [
            // Day 1 — text preview (free lesson)
            [
                'section_id'  => $day1->id,
                'sort_order'  => 1,
                'type'        => LessonType::Text,
                'is_preview'  => true,
                'title'       => ['ar' => 'مرحباً بك في الدورة', 'en' => 'Welcome to the Course'],
                'content'     => [
                    'ar' => '<p>أهلاً وسهلاً! هذه الدورة التجريبية تتيح لك استكشاف جميع ميزات نظام إدارة التعلم.</p><ul><li>دروس نصية وفيديو وملفات PDF</li><li>مرفقات قابلة للتنزيل</li><li>اختبار مع إمكانية إعادة التقديم</li></ul>',
                    'en' => '<p>Welcome! This sandbox course lets you explore every feature the LMS offers.</p><ul><li>Text, video and PDF lessons</li><li>Downloadable attachments</li><li>A quiz with re-submission enabled</li></ul>',
                ],
                'video_url'   => null,
            ],
            // Day 1 — second text lesson
            [
                'section_id'  => $day1->id,
                'sort_order'  => 2,
                'type'        => LessonType::Text,
                'is_preview'  => false,
                'title'       => ['ar' => 'أهداف التعلم', 'en' => 'Learning Objectives'],
                'content'     => [
                    'ar' => '<p>بنهاية هذه الدورة ستكون قادراً على التنقل بين محتويات النظام بثقة.</p>',
                    'en' => '<p>By the end of this course you will be able to navigate all LMS content types with confidence.</p>',
                ],
                'video_url'   => null,
            ],
            // Day 2 — video lesson
            [
                'section_id'  => $day2->id,
                'sort_order'  => 3,
                'type'        => LessonType::Video,
                'is_preview'  => false,
                'title'       => ['ar' => 'درس فيديو تجريبي', 'en' => 'Sample Video Lesson'],
                'content'     => [
                    'ar' => '<p>شاهد هذا الفيديو التعريفي لفهم آلية عمل النظام.</p>',
                    'en' => '<p>Watch this introductory video to understand how the system works.</p>',
                ],
                'video_url'   => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            // Day 2 — PDF lesson
            [
                'section_id'  => $day2->id,
                'sort_order'  => 4,
                'type'        => LessonType::Pdf,
                'is_preview'  => false,
                'title'       => ['ar' => 'ملف دراسي (PDF)', 'en' => 'Study Guide (PDF)'],
                'content'     => [
                    'ar' => '<p>ملف دراسي تجريبي. في بيئة الإنتاج ارفع ملف PDF حقيقياً.</p>',
                    'en' => '<p>Placeholder PDF lesson. In production, upload a real PDF file.</p>',
                ],
                'video_url'   => null,
            ],
            // Day 3 — wrap-up
            [
                'section_id'  => $day3->id,
                'sort_order'  => 5,
                'type'        => LessonType::Text,
                'is_preview'  => false,
                'title'       => ['ar' => 'ملخص الدورة', 'en' => 'Course Summary'],
                'content'     => [
                    'ar' => '<p>لقد أتممت جميع دروس الدورة. أكمل الاختبار أدناه لاختبار إمكانية إعادة التقديم.</p>',
                    'en' => '<p>You have completed all lessons. Take the quiz below to test the re-submission feature.</p>',
                ],
                'video_url'   => null,
            ],
        ];

        foreach ($lessons as $data) {
            Lesson::query()->updateOrCreate(
                ['course_id' => $course->id, 'sort_order' => $data['sort_order']],
                [
                    'course_curriculum_section_id' => $data['section_id'],
                    'title'      => $data['title'],
                    'content'    => $data['content'],
                    'type'       => $data['type'],
                    'is_preview' => $data['is_preview'],
                    'video_url'  => $data['video_url'],
                ]
            );
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, CourseCurriculumSection> $sections
     */
    private function seedAttachments(Course $course, \Illuminate\Support\Collection $sections): void
    {
        $attachments = [
            [
                'section_sort' => 1,
                'title'        => ['ar' => 'دليل المتعلم', 'en' => 'Learner Guide'],
                'file_path'    => ['lms-test/learner-guide.pdf'],
                'visibility'   => AttachmentVisibility::EnrolledOnly,
            ],
            [
                'section_sort' => 2,
                'title'        => ['ar' => 'ملف التمارين', 'en' => 'Exercise Workbook'],
                'file_path'    => ['lms-test/exercise-workbook.pdf'],
                'visibility'   => AttachmentVisibility::EnrolledOnly,
            ],
            [
                'section_sort' => 3,
                'title'        => ['ar' => 'ورقة الغش (مرجع سريع)', 'en' => 'Cheat Sheet (Quick Reference)'],
                'file_path'    => ['lms-test/cheat-sheet.pdf'],
                'visibility'   => AttachmentVisibility::Public,
            ],
        ];

        foreach ($attachments as $data) {
            $section = $sections->firstWhere('sort_order', $data['section_sort']);

            CourseAttachment::query()->updateOrCreate(
                [
                    'course_id'                    => $course->id,
                    'course_curriculum_section_id' => $section->id,
                ],
                [
                    'title'      => $data['title'],
                    'file_path'  => $data['file_path'],
                    'visibility' => $data['visibility'],
                ]
            );
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int, CourseCurriculumSection> $sections
     */
    private function seedQuiz(Course $course, CourseGroup $group, \Illuminate\Support\Collection $sections): void
    {
        $day3 = $sections->firstWhere('sort_order', 3);

        $quiz = Quiz::query()->updateOrCreate(
            [
                'course_id'                    => $course->id,
                'course_group_id'              => $group->id,
                'course_curriculum_section_id' => $day3->id,
            ],
            [
                'title'          => 'Final Knowledge Check',
                'passing_score'  => 60,
                'allow_resubmit' => true,
                'max_attempts'   => 3,
            ]
        );

        // Remove old questions so re-running the seeder is clean
        $quiz->questions()->each(fn ($q) => $q->options()->delete());
        $quiz->questions()->delete();

        $this->createMcqQuestion(
            $quiz,
            question: 'Which lesson type allows uploading a static document for learners to read?',
            options: [
                ['label' => 'Text',  'is_correct' => false],
                ['label' => 'Video', 'is_correct' => false],
                ['label' => 'PDF',   'is_correct' => true],
                ['label' => 'Live session', 'is_correct' => false],
            ],
            points: 2,
        );

        $this->createMcqQuestion(
            $quiz,
            question: 'What does "allow_resubmit" on a quiz control?',
            options: [
                ['label' => 'Whether the quiz can be edited by instructors', 'is_correct' => false],
                ['label' => 'Whether students may take the quiz more than once', 'is_correct' => true],
                ['label' => 'Whether the quiz is visible in the catalog', 'is_correct' => false],
                ['label' => 'Whether quiz results are shared publicly', 'is_correct' => false],
            ],
            points: 2,
        );

        $this->createMcqQuestion(
            $quiz,
            question: 'Which group status makes a course group visible for enrollment?',
            options: [
                ['label' => 'Archived', 'is_correct' => false],
                ['label' => 'Closed',   'is_correct' => false],
                ['label' => 'Open',     'is_correct' => true],
                ['label' => 'Pending',  'is_correct' => false],
            ],
            points: 2,
        );

        $this->createTrueFalseQuestion(
            $quiz,
            question: 'True or False: A quiz attempt is saved even when the student fails.',
            correctAnswer: true,
            points: 1,
        );

        $this->createTrueFalseQuestion(
            $quiz,
            question: 'True or False: Setting max_attempts to null prevents re-submission entirely.',
            correctAnswer: false,
            points: 1,
        );
    }

    private function seedEnrollments(Course $course, CourseGroup $group): void
    {
        $emailsToEnroll = [
            'student1@example.com',
            'mostafa.mohamed@naqi.sa',
        ];

        foreach ($emailsToEnroll as $email) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name'     => 'Test Student ('.$email.')',
                    'email'    => $email,
                    'password' => Hash::make('password'),
                ]);
            }

            Enrollment::query()->firstOrCreate(
                ['user_id' => $user->id, 'course_group_id' => $group->id],
                [
                    'course_id'    => $course->id,
                    'status'       => EnrollmentStatus::Active,
                    'activated_at' => now(),
                    'is_invited'   => false,
                ]
            );
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<int, array{label: string, is_correct: bool}>  $options
     */
    private function createMcqQuestion(Quiz $quiz, string $question, array $options, int $points = 1): QuizQuestion
    {
        $q = QuizQuestion::create([
            'quiz_id'  => $quiz->id,
            'question' => $question,
            'type'     => QuizQuestionType::Mcq,
            'points'   => $points,
        ]);

        foreach ($options as $opt) {
            QuizOption::create([
                'quiz_question_id' => $q->id,
                'label'            => $opt['label'],
                'is_correct'       => $opt['is_correct'],
            ]);
        }

        return $q;
    }

    private function createTrueFalseQuestion(Quiz $quiz, string $question, bool $correctAnswer, int $points = 1): QuizQuestion
    {
        $q = QuizQuestion::create([
            'quiz_id'  => $quiz->id,
            'question' => $question,
            'type'     => QuizQuestionType::TrueFalse,
            'points'   => $points,
        ]);

        QuizOption::create(['quiz_question_id' => $q->id, 'label' => 'True',  'is_correct' => $correctAnswer]);
        QuizOption::create(['quiz_question_id' => $q->id, 'label' => 'False', 'is_correct' => ! $correctAnswer]);

        return $q;
    }
}
