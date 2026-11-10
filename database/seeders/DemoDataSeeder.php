<?php

namespace Database\Seeders;

use App\Enums\AttachmentVisibility;
use App\Enums\BankTransferStatus;
use App\Enums\CertificateStatus;
use App\Enums\CouponTargetType;
use App\Enums\CouponType;
use App\Enums\CourseGroupStatus;
use App\Enums\CourseStatus;
use App\Enums\DeliveryType;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\QuizQuestionType;
use App\Models\BankTransferReceipt;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CouponTarget;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Page;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\Section;
use App\Models\User;
use App\Models\WishlistItem;
use App\Services\SectionFieldNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->resetDemoData();

        [$admin, $trainerA, $trainerB, $studentA, $studentB] = $this->seedUsers();

        $categories = $this->seedCategories();

        $courses = $this->seedCourses($categories);

        $discounts = $this->seedDiscounts($courses);

        $this->seedBundles($categories, $courses, $discounts);

        $groups = $this->seedGroupsAndSessions($courses, [$trainerA, $trainerB]);

        $lessons = $this->seedLessons($courses);

        $this->seedCourseAttachments($courses);

        $this->seedQuizzes($groups);

        $coupon = $this->seedCoupons($categories);

        $this->seedCommerceFlow($studentA, $coupon, $courses->first(), $groups, $admin);

        // $this->seedWishlist([$studentA, $studentB], $courses);

        $this->seedProgress($studentA, $lessons);

        $this->seedPages();

        $this->seedMenus();

        $this->outputSummary($discounts);
    }

    private function resetDemoData(): void
    {
        Schema::disableForeignKeyConstraints();

        $tables = [
            (new BankTransferReceipt)->getTable(),
            (new Payment)->getTable(),
            (new OrderItem)->getTable(),
            (new Order)->getTable(),
            (new CartItem)->getTable(),
            (new Cart)->getTable(),
            (new CouponRedemption)->getTable(),
            (new CouponTarget)->getTable(),
            (new Coupon)->getTable(),
            (new QuizOption)->getTable(),
            (new QuizQuestion)->getTable(),
            (new QuizAttempt)->getTable(),
            (new Quiz)->getTable(),
            (new LessonProgress)->getTable(),
            (new Certificate)->getTable(),
            (new Enrollment)->getTable(),
            (new CourseGroupSession)->getTable(),
            (new CourseCurriculumSection)->getTable(),
            (new CourseGroup)->getTable(),
            (new Lesson)->getTable(),
            (new CourseAttachment)->getTable(),
            (new WishlistItem)->getTable(),
            (new MenuItem)->getTable(),
            (new Menu)->getTable(),
            (new Section)->getTable(),
            (new Page)->getTable(),
            (new Course)->getTable(),
            (new Bundle)->getTable(),
            (new Category)->getTable(),
            (new Discount)->getTable(),
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::table('course_trainers')->truncate();
        DB::table('course_group_instructors')->truncate();
        DB::table('discount_courses')->truncate();
        DB::table('bundle_courses')->truncate();
        DB::table('discount_bundles')->truncate();

        Schema::enableForeignKeyConstraints();
    }

    private function seedUsers(): array
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );

        $trainerA = User::firstOrCreate(
            ['email' => 'trainer1@example.com'],
            ['name' => 'Hani Al-Mutairi', 'password' => Hash::make('password')]
        );

        $trainerB = User::firstOrCreate(
            ['email' => 'trainer2@example.com'],
            ['name' => 'Sara Al-Omari', 'password' => Hash::make('password')]
        );

        $studentA = User::firstOrCreate(
            ['email' => 'student1@example.com'],
            ['name' => 'Noura Al-Rasheed', 'password' => Hash::make('password')]
        );

        $studentB = User::firstOrCreate(
            ['email' => 'student2@example.com'],
            ['name' => 'Mohammed Al-Zahrani', 'password' => Hash::make('password')]
        );

        if (class_exists(Role::class) && method_exists($admin, 'assignRole')) {
            $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
            $trainerRole = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'admin']);
            $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

            $admin->syncRoles([$adminRole]);
            $trainerA->syncRoles([$trainerRole]);
            $trainerB->syncRoles([$trainerRole]);
            $studentA->syncRoles([$studentRole]);
            $studentB->syncRoles([$studentRole]);
        }

        if (method_exists($admin, 'syncPermissions')) {
            $admin->syncPermissions(Permission::query()->where('guard_name', 'admin')->get());
        }

        // Create superadmin user
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@miran-lms.net'],
            [
                'name' => 'superadmin',
                'password' => Hash::make('123456'),
            ]
        );

        $superAdminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $superAdmin->syncRoles([$superAdminRole]);
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'admin')->get());

        return [$admin, $trainerA, $trainerB, $studentA, $studentB];
    }

    private function seedCategories()
    {
        $categories = [
            ['en' => 'Business Leadership', 'ar' => 'القيادة في الأعمال'],
            ['en' => 'Project Management', 'ar' => 'إدارة المشاريع'],
            ['en' => 'Occupational Safety', 'ar' => 'السلامة المهنية'],
            ['en' => 'Digital Transformation', 'ar' => 'التحول الرقمي'],
        ];

        return collect($categories)->map(function (array $title) {
            return Category::create([
                'slug' => Str::slug($title['en']),
                'name' => ['en' => $title['en'], 'ar' => $title['ar']],
                'is_active' => true,
            ]);
        });
    }

    private function seedCourses($categories)
    {
        $coursesData = [
            [
                'title' => ['en' => 'Strategic Leadership Program', 'ar' => 'برنامج القيادة الاستراتيجية'],
                'summary' => ['en' => 'Lead with clarity and measurable impact.', 'ar' => 'قد بثقة وبأثر قابل للقياس.'],
                'description' => [
                    'en' => '<p>Leadership framework, practical tools, and case-driven learning.</p>',
                    'ar' => '<p>إطار عمل للقيادة، أدوات عملية، وتعلّم قائم على دراسات الحالة.</p>',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
                'intro_video_path' => null,
                'sneak_peek_images' => [
                    'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=1200&q=80',
                ],
                'intro_video_path' => 'https://filesamples.com/samples/video/mp4/sample_640x360.mp4',
                'sneak_peek_images' => [
                    'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 1800,
                'duration_hours' => 24,
                'duration_group' => 'long',
                'days_count' => 4,
                'time_from' => '18:00:00',
                'time_to' => '22:00:00',
                'delivery_type' => DeliveryType::RemoteLive,
                'sku' => 'ML-LEAD-001',
                'category_index' => 0,
            ],
            [
                'title' => ['en' => 'PMP Essentials Bootcamp', 'ar' => 'معسكر أساسيات PMP'],
                'summary' => ['en' => 'Prepare for PMP with structured practice.', 'ar' => 'استعد لاختبار PMP عبر تدريب منظم وموجّه.'],
                'description' => [
                    'en' => '<p>Scope, schedule, risk, and exam strategies.</p>',
                    'ar' => '<p>النطاق، الجدول الزمني، المخاطر، واستراتيجيات الاختبار.</p>',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80',
                'intro_video_path' => null,
                'sneak_peek_images' => [
                    'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 1500,
                'duration_hours' => 18,
                'duration_group' => 'long',
                'days_count' => 3,
                'time_from' => '17:00:00',
                'time_to' => '21:00:00',
                'delivery_type' => DeliveryType::Hybrid,
                'sku' => 'ML-PMP-101',
                'category_index' => 1,
            ],
            [
                'title' => ['en' => 'OSHA Safety Compliance', 'ar' => 'الالتزام بسلامة OSHA'],
                'summary' => ['en' => 'Create a safe and compliant workplace.', 'ar' => 'ابنِ بيئة عمل آمنة ومتوافقة مع المتطلبات.'],
                'description' => [
                    'en' => '<p>Risk mitigation, safety culture, and compliance checklists.</p>',
                    'ar' => '<p>تقليل المخاطر، ثقافة السلامة، وقوائم التحقق للالتزام.</p>',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1200&q=80',
                'intro_video_path' => null,
                'sneak_peek_images' => [
                    'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1457305237443-44c3d5a30b89?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1200&q=80',
                ],
                'price' => 1200,
                'duration_hours' => 12,
                'duration_group' => 'medium',
                'days_count' => 2,
                'time_from' => '09:00:00',
                'time_to' => '15:00:00',
                'delivery_type' => DeliveryType::Offline,
                'sku' => 'ML-SAFE-302',
                'category_index' => 2,
            ],
            [
                'title' => ['en' => 'Digital Transformation Roadmap', 'ar' => 'خارطة طريق التحول الرقمي'],
                'summary' => ['en' => 'Align technology, people, and process.', 'ar' => 'وحّد التقنية والموارد البشرية والعمليات.'],
                'description' => [
                    'en' => '<p>Roadmap design, KPI tracking, and change leadership.</p>',
                    'ar' => '<p>تصميم خارطة الطريق، متابعة مؤشرات الأداء، وقيادة التغيير.</p>',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
                'price' => 2100,
                'duration_hours' => 30,
                'duration_group' => 'extended',
                'days_count' => 5,
                'time_from' => '16:00:00',
                'time_to' => '21:00:00',
                'delivery_type' => DeliveryType::Online,
                'sku' => 'ML-DT-555',
                'category_index' => 3,
            ],
        ];

        $courses = collect();
        foreach ($coursesData as $index => $data) {
            $category = $categories[$data['category_index']];
            $slug = Str::slug($data['title']['en']);

            $course = Course::create([
                'category_id' => $category->id,
                'title' => $data['title'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'slug' => $slug,
                'image_path' => $data['image_path'],
                'intro_video_path' => $data['intro_video_path'] ?? null,
                'sneak_peek_images' => $data['sneak_peek_images'] ?? [],
                'price' => $data['price'],
                'start_date' => now()->addDays(10 + ($index * 3)),
                'source_url' => null,
                'sku' => $data['sku'],
                'duration_hours' => $data['duration_hours'],
                'duration_group' => $data['duration_group'],
                'days_count' => $data['days_count'],
                'time_from' => $data['time_from'],
                'time_to' => $data['time_to'],
                'delivery_type' => $data['delivery_type'],
                'status' => CourseStatus::Published,
            ]);

            $courses->push($course);
        }

        return $courses;
    }

    private function seedDiscounts($courses)
    {
        $percentDiscount = Discount::create([
            'type' => Discount::TYPE_PERCENTAGE,
            'amount' => 20,
            'max_amount' => 300,
            'is_active' => true,
        ]);

        $fixedDiscount = Discount::create([
            'type' => Discount::TYPE_FIXED,
            'amount' => 150,
            'max_amount' => null,
            'is_active' => true,
        ]);

        $inactiveDiscount = Discount::create([
            'type' => Discount::TYPE_PERCENTAGE,
            'amount' => 10,
            'max_amount' => null,
            'is_active' => false,
        ]);

        $courses[0]->discounts()->sync([$percentDiscount->id]);
        $courses[1]->discounts()->sync([$fixedDiscount->id]);
        $courses[2]->discounts()->sync([$inactiveDiscount->id]);

        return collect([$percentDiscount, $fixedDiscount, $inactiveDiscount]);
    }

    private function seedBundles($categories, $courses, $discounts): void
    {
        $bundles = [
            [
                'title' => [
                    'en' => 'Cyber Security Bundle',
                    'ar' => 'حزمة الأمن السيبراني',
                ],
                'summary' => [
                    'en' => 'Essential cyber security coverage for teams.',
                    'ar' => 'تغطية أساسية في الأمن السيبراني مخصصة للفرق.',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=1200&q=80',
                'price' => 4399,
                'category_index' => 2,
                'course_indexes' => [2, 3],
                'discount_id' => $discounts->first()?->id,
            ],
            [
                'title' => [
                    'en' => 'AI Learning Bundle',
                    'ar' => 'حزمة تعلم الذكاء الاصطناعي',
                ],
                'summary' => [
                    'en' => 'Analytics and transformation essentials.',
                    'ar' => 'أساسيات التحليل والتحول الرقمي.',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1200&q=80',
                'price' => 2599,
                'category_index' => 3,
                'course_indexes' => [0, 3],
                'discount_id' => $discounts->skip(1)->first()?->id,
            ],
            [
                'title' => [
                    'en' => 'Design Essentials Bundle',
                    'ar' => 'حزمة أساسيات التصميم',
                ],
                'summary' => [
                    'en' => 'Foundational programs for creative teams.',
                    'ar' => 'برامج تأسيسية مخصصة للفرق الإبداعية.',
                ],
                'image_path' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1200&q=80',
                'price' => 1799,
                'category_index' => 0,
                'course_indexes' => [0, 1],
                'discount_id' => null,
            ],
        ];

        foreach ($bundles as $bundleData) {
            $category = $categories[$bundleData['category_index']];
            $slug = \Illuminate\Support\Str::slug($bundleData['title']['en']);

            $bundle = Bundle::create([
                'category_id' => $category->id,
                'title' => $bundleData['title'],
                'summary' => $bundleData['summary'],
                'description' => $bundleData['summary'],
                'slug' => $slug,
                'image_path' => null,
                'image_path' => $bundleData['image_path'],
                'price' => $bundleData['price'],
                'is_active' => true,
            ]);

            $bundle->courses()->sync(
                collect($bundleData['course_indexes'])
                    ->map(fn ($index) => $courses[$index]->id)
                    ->all()
            );

            if ($bundleData['discount_id']) {
                $bundle->discounts()->sync([$bundleData['discount_id']]);
            }
        }
    }

    private function seedGroupsAndSessions($courses, array $instructors)
    {
        $groups = collect();

        foreach ($courses as $courseIndex => $course) {
            $startBase = now()->addDays(5 + ($courseIndex * 4));

            for ($i = 0; $i < 2; $i++) {
                $startDate = $startBase->copy()->addWeeks($i * 3);
                $endDate = $startDate->copy()->addDays(10 + ($courseIndex * 2));

                $title = $startDate->format('d-m-Y');

                $group = CourseGroup::create([
                    'course_id' => $course->id,
                    'title' => ['en' => $title, 'ar' => $title],
                    'group_price_amount' => (int) round($course->price + ($i * 150)),
                    'start_at' => $startDate,
                    'end_at' => $endDate,
                    'capacity' => 25 + ($courseIndex * 5),
                    'enrollment_deadline_at' => $startDate->copy()->subDays(2),
                    'status' => $i === 0 ? CourseGroupStatus::Open : CourseGroupStatus::Closed,
                ]);

                $group->instructors()->sync(
                    collect($instructors)->mapWithKeys(fn ($instructor) => [
                        $instructor->id => ['course_id' => $course->id],
                    ])->all()
                );

                $day1 = CourseCurriculumSection::create([
                    'course_id' => $course->id,
                    'course_group_id' => $group->id,
                    'title' => ['en' => 'Day 1', 'ar' => 'اليوم الأول'],
                    'sort_order' => 1,
                ]);

                $day2 = CourseCurriculumSection::create([
                    'course_id' => $course->id,
                    'course_group_id' => $group->id,
                    'title' => ['en' => 'Day 2', 'ar' => 'اليوم الثاني'],
                    'sort_order' => 2,
                ]);

                CourseGroupSession::create([
                    'course_group_id' => $group->id,
                    'course_curriculum_section_id' => $day1->id,
                    'title' => 'Kickoff session',
                    'start_at' => $startDate->copy()->addDays(1)->setTime(18, 0),
                    'duration_minutes' => 90,
                    'join_url' => 'https://example.com/meet',
                    'recording_url' => null,
                ]);

                CourseGroupSession::create([
                    'course_group_id' => $group->id,
                    'course_curriculum_section_id' => $day2->id,
                    'title' => 'Workshop clinic',
                    'start_at' => $startDate->copy()->addDays(4)->setTime(18, 0),
                    'duration_minutes' => 120,
                    'join_url' => 'https://example.com/meet',
                    'recording_url' => 'https://example.com/recording',
                ]);

                $groups->push($group);
            }
        }

        return $groups;
    }

    private function seedLessons($courses)
    {
        $lessons = collect();

        foreach ($courses as $courseIndex => $course) {
            $textLesson = Lesson::create([
                'course_id' => $course->id,
                'title' => ['en' => 'Welcome & roadmap', 'ar' => 'مرحباً وخارطة الطريق'],
                'content' => [
                    'en' => '<p>Welcome to the program.</p><p>Key milestones and weekly outcomes.</p><ul><li>Orientation</li><li>Core frameworks</li><li>Applied project</li></ul><p>Visit <strong>https://example.com/resources</strong> for resources.</p><p><img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80" alt="course" /></p>',
                    'ar' => '<p>مرحباً بك في البرنامج.</p><p>المحطات الرئيسية ومخرجات كل أسبوع.</p><ul><li>جلسة تعريفية</li><li>الأطر الأساسية</li><li>مشروع تطبيقي</li></ul><p>زر <strong>https://example.com/resources</strong> للوصول إلى الموارد.</p><p><img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80" alt="course" /></p>',
                ],
                'type' => LessonType::Text,
                'is_preview' => true,
                'sort_order' => 1,
            ]);

            $videoLesson = Lesson::create([
                'course_id' => $course->id,
                'title' => ['en' => 'Core concepts video', 'ar' => 'فيديو المفاهيم الأساسية'],
                'content' => [
                    'en' => '<p>Short video walkthrough with key concepts.</p>',
                    'ar' => '<p>شرح فيديو قصير يغطّي أهم المفاهيم.</p>',
                ],
                'type' => LessonType::Video,
                'video_url' => 'https://example.com/video',
                'is_preview' => false,
                'sort_order' => 2,
            ]);

            $pdfLesson = Lesson::create([
                'course_id' => $course->id,
                'title' => ['en' => 'Toolkit PDF', 'ar' => 'ملف الأدوات (PDF)'],
                'content' => [
                    'en' => '<p>Download and review the toolkit PDF.</p>',
                    'ar' => '<p>قم بتنزيل ملف الأدوات بصيغة PDF وراجعه.</p>',
                ],
                'type' => LessonType::Pdf,
                'pdf_path' => 'files/toolkit.pdf',
                'is_preview' => false,
                'sort_order' => 3,
            ]);

            $lessons->push($textLesson, $videoLesson, $pdfLesson);

            if ($courseIndex % 2 === 0) {
                $extraLesson = Lesson::create([
                    'course_id' => $course->id,
                    'title' => ['en' => 'Applied practice', 'ar' => 'تطبيق عملي'],
                    'content' => [
                        'en' => '<p>Submit a short reflection and case study summary.</p>',
                        'ar' => '<p>قدّم تأملاً قصيراً وملخصاً لدراسة حالة.</p>',
                    ],
                    'type' => LessonType::Text,
                    'is_preview' => false,
                    'sort_order' => 4,
                ]);

                $lessons->push($extraLesson);
            }
        }

        return $lessons;
    }

    private function seedCourseAttachments($courses): void
    {
        foreach ($courses as $course) {
            CourseAttachment::create([
                'course_id' => $course->id,
                'title' => ['en' => 'Course outline', 'ar' => 'مخطط الدورة'],
                'file_path' => 'files/course-outline.pdf',
                'visibility' => AttachmentVisibility::Public,
            ]);

            CourseAttachment::create([
                'course_id' => $course->id,
                'title' => ['en' => 'Workshop workbook', 'ar' => 'دفتر عمل الورشة'],
                'file_path' => 'files/workbook.pdf',
                'visibility' => AttachmentVisibility::EnrolledOnly,
            ]);
        }
    }

    private function seedQuizzes($groups): void
    {
        foreach ($groups as $group) {
            $sections = CourseCurriculumSection::query()
                ->where('course_group_id', $group->id)
                ->orderBy('sort_order')
                ->get();

            foreach ($sections as $section) {
                $quiz = Quiz::create([
                    'course_id' => $group->course_id,
                    'course_curriculum_section_id' => $section->id,
                    'title' => $section->getTranslation('title', 'en').' — Knowledge check',
                    'passing_score' => 70,
                ]);

                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => 'Which statement is correct?',
                    'type' => QuizQuestionType::Mcq,
                    'points' => 2,
                ]);

                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => 'Option A',
                    'is_correct' => true,
                ]);

                QuizOption::create([
                    'quiz_question_id' => $question->id,
                    'label' => 'Option B',
                    'is_correct' => false,
                ]);

                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question' => 'True or False: Planning reduces project risk.',
                    'type' => QuizQuestionType::TrueFalse,
                    'points' => 1,
                ]);
            }
        }
    }

    private function seedCoupons($categories): Coupon
    {
        $activeCoupon = Coupon::create([
            'title' => ['en' => 'Welcome discount', 'ar' => 'خصم ترحيبي'],
            'code' => 'WELCOME10',
            'type' => CouponType::Percent,
            'value' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(30),
            'usage_limit' => 200,
            'per_user_limit' => 1,
            'min_order_amount' => 500,
            'is_active' => true,
        ]);

        Coupon::create([
            'title' => ['en' => 'Expired spring offer', 'ar' => 'عرض الربيع المنتهي'],
            'code' => 'SPRING5',
            'type' => CouponType::Fixed,
            'value' => 50,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
            'usage_limit' => 50,
            'per_user_limit' => 1,
            'min_order_amount' => 300,
            'is_active' => false,
        ]);

        $targetCategory = $categories->first();
        CouponTarget::create([
            'coupon_id' => $activeCoupon->id,
            'target_type' => CouponTargetType::Category,
            'target_id' => $targetCategory->id,
        ]);

        return $activeCoupon;
    }

    private function seedCommerceFlow(User $student, Coupon $coupon, Course $course, $groups, User $admin): void
    {
        $group = $groups->firstWhere('course_id', $course->id) ?? $course->groups()->first();
        if (! $group) {
            return;
        }

        $price = (int) $group->group_price_amount;
        $discount = (int) round($price * 0.1);
        $total = max(0, $price - $discount);

        $cart = Cart::create([
            'user_id' => $student->id,
            'coupon_id' => $coupon->id,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => $price,
            'quantity' => 1,
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'order_no' => 'ORDER-1001',
            'status' => OrderStatus::Paid,
            'currency' => 'SAR',
            'subtotal_amount' => $price,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'coupon_id' => $coupon->id,
            'notes' => 'Seeded order with coupon discount',
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course',
            'price_amount' => $price,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'subtitle' => __('web.cart.course_group'),
                'course_group_id' => $group->id,
                'course_id' => $course->id,
                'course_slug' => $course->slug,
                'image_path' => $course->image_path,
            ],
            'quantity' => 1,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'seed',
            'provider_reference' => 'PAY-1001',
            'status' => PaymentStatus::Paid,
            'amount' => $total,
            'payload' => ['source' => 'seed'],
        ]);

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $student->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);

        Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now()->subDays(1),
        ]);

        Certificate::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => CertificateStatus::Approved,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'file_path' => 'certificates/cert-1001.pdf',
        ]);

        $quiz = $course->quizzes()->first();
        if ($quiz) {
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $student->id,
                'score' => 85,
                'answers' => ['q1' => 'Option A'],
            ]);
        }

        $pendingOrder = Order::create([
            'user_id' => $student->id,
            'order_no' => 'ORDER-1002',
            'status' => OrderStatus::Pending,
            'currency' => 'SAR',
            'subtotal_amount' => $price,
            'discount_amount' => 0,
            'total_amount' => $price,
            'coupon_id' => null,
            'notes' => 'Bank transfer order',
        ]);

        BankTransferReceipt::create([
            'order_id' => $pendingOrder->id,
            'user_id' => $student->id,
            'receipt_path' => 'bank-receipts/receipt-1002.jpg',
            'status' => BankTransferStatus::Submitted,
        ]);
    }

    private function seedWishlist(array $students, $courses): void
    {
        foreach ($students as $student) {
            $courses->shuffle()->take(2)->each(function ($course) use ($student) {
                WishlistItem::updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'wishable_type' => Course::class,
                        'wishable_id' => $course->id,
                    ],
                    []
                );
            });
        }
    }

    private function seedProgress(User $student, $lessons): void
    {
        $lessons->take(3)->each(function (Lesson $lesson) use ($student) {
            LessonProgress::create([
                'user_id' => $student->id,
                'lesson_id' => $lesson->id,
                'completed_at' => now()->subDays(2),
            ]);
        });
    }

    private function seedPages(): void
    {
        // Create Home page with sections
        $homePage = Page::create([
            'title' => ['en' => 'Home', 'ar' => 'الرئيسية'],
            'slug' => 'home',
            'body' => ['en' => 'Welcome to our learning platform', 'ar' => 'مرحباً بك في منصة التعلم الخاصة بنا'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        // Home page sections from PagesSectionMenuSeeder
        $homeSections = [
            [
                'type' => 'hero',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'tag_en' => 'Trusted training',
                    'tag_ar' => 'تدريب موثوق',
                    'heading_en' => 'Learn with confidence',
                    'heading_ar' => 'تعلّم بثقة',
                    'subheading_en' => 'Build skills with curated courses and expert instructors.',
                    'subheading_ar' => 'طوّر مهاراتك عبر دورات مختارة بعناية ومدربين خبراء.',
                    'search_placeholder_en' => 'Search courses',
                    'search_placeholder_ar' => 'ابحث عن الدورات',
                    'category_placeholder_en' => 'Select category',
                    'category_placeholder_ar' => 'اختر التصنيف',
                    'search_cta_en' => 'Search',
                    'search_cta_ar' => 'بحث',
                    'slides' => [
                        [
                            'heading_en' => 'Learn with confidence',
                            'heading_ar' => 'تعلّم بثقة',
                            'subheading_en' => 'Build skills with curated courses and expert instructors.',
                            'subheading_ar' => 'طوّر مهاراتك عبر دورات مختارة بعناية ومدربين خبراء.',
                            'background_image' => null,
                        ],
                    ],
                    'primary_button_text_en' => 'Browse courses',
                    'primary_button_text_ar' => 'تصفح الدورات',
                    'primary_button_url' => '/courses',
                    'secondary_button_text_en' => 'Contact us',
                    'secondary_button_text_ar' => 'تواصل معنا',
                    'secondary_button_url' => '/#contact',
                ],
                'sort_order' => 10,
            ],
            [
                'type' => 'action_cards',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'items' => [
                        [
                            'icon' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 6.5L10 13l-2.5-2.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12a9 9 0 1 1-4.3-7.7" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                            'text_en' => 'For Order leave  phone',
                            'text_ar' => 'للطلب سجل رقمك',
                            'url' => '/orders/track',
                        ],
                        [
                            'icon' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="white" stroke-width="2"/><path d="M20 21a8 8 0 1 0-16 0" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>',
                            'text_en' => 'Train Companies & Institutions',
                            'text_ar' => 'تدريب الشركات والمؤسسات',
                            'url' => '/corporate',
                        ],
                        [
                            'icon' => '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 9h8M8 13h6M6 3h12a2 2 0 0 1 2 2v14l-4-3H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                            'text_en' => 'Have Complaint or Need Help',
                            'text_ar' => 'لديك شكوى أو تحتاج للمساعدة',
                            'url' => '/complaints',
                        ],
                    ],
                ],
                'sort_order' => 15,
            ],
            [
                'type' => 'categories',
                'title' => 'Explore Diverse Learning Paths at',
                'subtitle' => "We believe in empowering individuals through knowledge. Our diverse range of courses spans various fields, ensuring there's something for everyone. Unleash your potential and explore our carefully curated categories.",
                'content' => [
                    'title_en' => 'Explore Diverse Learning Paths at',
                    'title_ar' => 'استكشف مسارات تعلم متنوعة في',
                    'subtitle_en' => "We believe in empowering individuals through knowledge. Our diverse range of courses spans various fields, ensuring there's something for everyone. Unleash your potential and explore our carefully curated categories.",
                    'subtitle_ar' => 'نؤمن بتمكين الأفراد عبر المعرفة. مجموعة دوراتنا المتنوعة تغطي مجالات متعددة لتناسب الجميع. أطلق إمكاناتك واستكشف تصنيفاتنا المختارة بعناية.',
                    'category_label_en' => 'Category',
                    'category_label_ar' => 'التصنيف',
                    'view_courses_label_en' => 'View courses',
                    'view_courses_label_ar' => 'عرض الدورات',
                ],
                'sort_order' => 20,
            ],
            [
                'type' => 'featured_courses',
                'title' => 'Most requested courses',
                'subtitle' => 'Popular choices this season',
                'content' => [
                    'title_en' => 'Most requested courses',
                    'title_ar' => 'الدورات الأكثر طلبًا',
                    'subtitle_en' => 'Popular choices this season',
                    'subtitle_ar' => 'الاختيارات الأكثر شيوعًا هذا الموسم',
                    'discount_badge_en' => 'Discount',
                    'discount_badge_ar' => 'خصم',
                    'button_text_en' => 'Discover More',
                    'button_text_ar' => 'اكتشف المزيد',
                    'button_url' => '/courses',
                ],
                'sort_order' => 30,
            ],
            [
                'type' => 'cta',
                'title' => 'Get your accredited certificate with us!',
                'subtitle' => 'Experience the collaboration of numerous creators and an expanding selection of courses.',
                'content' => [
                    'title_en' => 'Get your accredited certificate with us!',
                    'title_ar' => 'احصل على شهادتك المعتمدة معنا!',
                    'text_en' => 'Experience the collaboration of numerous creators and an expanding selection of courses.',
                    'text_ar' => 'استمتع بتجربة تعلم غنية عبر مجموعة متنامية من الدورات والمحتوى المتخصص.',
                    'button_text_en' => 'Join Us Now',
                    'button_text_ar' => 'انضم الآن',
                    'button_url' => '/register',
                    'logos' => [],
                    'image' => null,
                ],
                'sort_order' => 40,
            ],
            [
                'type' => 'special_bundles',
                'title' => 'Special bundles',
                'subtitle' => 'Exclusive bundle deals built from our most requested courses.',
                'content' => [
                    'button_text_en' => 'Discover More',
                    'button_text_ar' => 'اكتشف المزيد',
                    'button_url' => '/bundles',
                ],
                'sort_order' => 50,
            ],
            [
                'type' => 'how_it_works',
                'title' => 'How it works',
                'subtitle' => 'Elit quam cras sollicitudin ridiculus commodo a eget.',
                'content' => [
                    'title_en' => 'How it works',
                    'title_ar' => 'كيف يعمل',
                    'subtitle_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                    'subtitle_ar' => 'خطوات بسيطة تمنحك تجربة تعلم منظمة وفعّالة من البداية حتى تحقيق الهدف.',
                    'feature_title_en' => 'Learn with various materials and various fields in a modern way',
                    'feature_title_ar' => 'تعلّم بمواد متنوعة وفي مجالات مختلفة بطريقة حديثة',
                    'feature_body_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                    'feature_body_ar' => 'محتوى مرن يجمع بين الفيديو والمواد المساندة والتطبيق العملي لتحقيق أفضل النتائج.',
                    'cta_text_en' => 'About us',
                    'cta_text_ar' => 'من نحن',
                    'cta_url' => '/about',
                    'stat_value' => '200+',
                    'stat_label_en' => 'Courses',
                    'stat_label_ar' => 'دورات',
                    'skill_label_en' => 'Your level skills',
                    'skill_label_ar' => 'مستوى مهاراتك',
                    'skill_percent' => 100,
                    'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1200&q=80',
                    'cards' => [
                        [
                            'title_en' => 'Discover Course',
                            'title_ar' => 'استكشف الدورات',
                            'body_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                            'body_ar' => 'تصفح الدورات حسب التصنيف، واختر ما يناسب هدفك ومستواك بسهولة.',
                            'icon' => 'discover',
                        ],
                        [
                            'title_en' => 'Get Access to Course',
                            'title_ar' => 'احصل على الوصول للدورة',
                            'body_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                            'body_ar' => 'اشترك وابدأ التعلم فورًا عبر محتوى منظّم وخطّة واضحة.',
                            'icon' => 'access',
                        ],
                        [
                            'title_en' => 'Share to your friends',
                            'title_ar' => 'شارك مع أصدقائك',
                            'body_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                            'body_ar' => 'شارك التجربة مع من تحب وادعُ فريقك للتعلم معًا.',
                            'icon' => 'share',
                        ],
                    ],
                ],
                'sort_order' => 60,
            ],
            [
                'type' => 'trainer_quotes',
                'title' => 'Valuable words from our famous trainers',
                'subtitle' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna',
                'content' => [
                    'title_en' => 'Valuable words from our famous instructors',
                    'title_ar' => 'كلمات ملهمة من مدربينا المميزين',
                    'subtitle_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna',
                    'subtitle_ar' => 'رسائل قصيرة ومحفّزة تساعدك على الاستمرار وتحقيق نتائج أفضل.',
                    'items' => [
                        [
                            'headline_en' => "Think Smart\nDiscover More",
                            'headline_ar' => "فكّر بذكاء\nواكتشف المزيد",
                            'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=80',
                        ],
                        [
                            'headline_en' => "Short way\nFor Success\nVariety\nof Contents",
                            'headline_ar' => "طريق مختصر\nللنجاح\nوتنوع\nفي المحتوى",
                            'image' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&q=80',
                        ],
                        [
                            'headline_en' => "Trusted.\nTested.\nGuaranteed.\nOnly Maran",
                            'headline_ar' => "موثوق.\nمجرّب.\nمضمون.\nفقط مران",
                            'image' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&q=80',
                        ],
                        [
                            'headline_en' => "Think Smart\nDiscover More",
                            'headline_ar' => "فكّر بذكاء\nواكتشف المزيد",
                            'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=80',
                        ],
                        [
                            'headline_en' => "Short way\nFor Success\nVariety\nof Contents",
                            'headline_ar' => "طريق مختصر\nللنجاح\nوتنوع\nفي المحتوى",
                            'image' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&q=80',
                        ],
                        [
                            'headline_en' => "Trusted.\nTested.\nGuaranteed.\nOnly Maran",
                            'headline_ar' => "موثوق.\nمجرّب.\nمضمون.\nفقط مران",
                            'image' => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&q=80',
                        ],
                    ],
                ],
                'sort_order' => 70,
            ],
            [
                'type' => 'testimonials_strip',
                'title' => 'What they say about us',
                'subtitle' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna',
                'content' => [
                    'title_en' => 'What they say about us',
                    'title_ar' => 'ماذا يقولون عنا',
                    'subtitle_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna',
                    'subtitle_ar' => 'آراء حقيقية من متعلمين شاركونا تجربتهم معنا.',
                    'items' => [
                        [
                            'text_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                            'text_ar' => 'تجربة ممتازة ومحتوى مرتب وسهل التطبيق. استفدت كثيرًا في عملي.',
                            'name_en' => 'Ahmed Omar',
                            'name_ar' => 'أحمد عمر',
                            'avatar' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=200&q=80',
                        ],
                        [
                            'text_en' => 'Nec tortor, vehicula nunc, duis orci. Nunc urna, vitae sit sapien diam diam.',
                            'text_ar' => 'أسلوب شرح واضح ودعم سريع. أنصح بها لأي شخص يريد تطوير نفسه.',
                            'name_en' => 'Mohamed Tarek',
                            'name_ar' => 'محمد طارق',
                            'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=200&q=80',
                        ],
                        [
                            'text_en' => 'Cras tempus venenatis consequat, in amet urna, tempor. Augue elit, ultrices sapien.',
                            'text_ar' => 'محتوى عملي وواقعي، والتجربة كانت أكثر من رائعة.',
                            'name_en' => 'Salma Alaa',
                            'name_ar' => 'سلمى علاء',
                            'avatar' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=200&q=80',
                        ],
                        [
                            'text_en' => 'Elit quam cras sollicitudin ridiculus commodo a eget. Quisque magna tempus urna lectus.',
                            'text_ar' => 'تجربة ممتازة ومحتوى مرتب وسهل التطبيق. استفدت كثيرًا في عملي.',
                            'name_en' => 'Ahmed Omar',
                            'name_ar' => 'أحمد عمر',
                            'avatar' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=200&q=80',
                        ],
                        [
                            'text_en' => 'Nec tortor, vehicula nunc, duis orci. Nunc urna, vitae sit sapien diam diam.',
                            'text_ar' => 'أسلوب شرح واضح ودعم سريع. أنصح بها لأي شخص يريد تطوير نفسه.',
                            'name_en' => 'Mohamed Tarek',
                            'name_ar' => 'محمد طارق',
                            'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=200&q=80',
                        ],
                        [
                            'text_en' => 'Cras tempus venenatis consequat, in amet urna, tempor. Augue elit, ultrices sapien.',
                            'text_ar' => 'محتوى عملي وواقعي، والتجربة كانت أكثر من رائعة.',
                            'name_en' => 'Salma Alaa',
                            'name_ar' => 'سلمى علاء',
                            'avatar' => 'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=200&q=80',
                        ],
                    ],
                ],
                'sort_order' => 80,
            ],
            [
                'type' => 'cta_banner',
                'title' => 'We develop your employees... with accredited training certificates.',
                'subtitle' => 'Invest in your team with accredited training programs that elevate performance across the organization.',
                'content' => [
                    'title_en' => 'We develop your employees... with accredited training certificates.',
                    'title_ar' => 'نطوّر موظفيك… بشهادات تدريب معتمدة',
                    'subtitle_en' => "Invest in your team's capabilities through accredited training programs that improve performance quality within the organization.",
                    'subtitle_ar' => 'استثمر في قدرات فريقك عبر برامج تدريب معتمدة تُحسّن جودة الأداء داخل المؤسسة.',
                    'button_text_en' => 'Start Develop Your Team Now',
                    'button_text_ar' => 'ابدأ تطوير فريقك الآن',
                    'button_url' => '/contact-us',
                    'background_image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80',
                ],
                'sort_order' => 90,
            ],
            [
                'type' => 'partners',
                'title' => 'Our Trusted Partners',
                'subtitle' => null,
                'content' => [
                    'title_en' => 'Our Trusted Partners',
                    'title_ar' => 'شركاؤنا الموثوقون',
                    'layout' => 'grid',
                    'logos' => [
                        'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png',
                        'https://themiran.net/wp-content/uploads/2023/11/New_Project-removebg-preview-e1699446752333.png',
                        'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg',
                        'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=400&q=80',
                        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=400&q=80',
                        'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80',
                    ],
                ],
                'sort_order' => 100,
            ],

            [
                'type' => 'why',
                'title' => 'Why choose us',
                'subtitle' => 'Practical training that delivers results',
                'active' => false,
                'content' => [
                    'title_en' => 'Why choose us',
                    'title_ar' => 'لماذا تختارنا',
                    'subtitle_en' => 'Practical training that delivers results',
                    'subtitle_ar' => 'تدريب عملي يحقق نتائج ملموسة',
                    'points' => [
                        [
                            'title_en' => 'Customized programs',
                            'title_ar' => 'برامج مخصّصة',
                            'body_en' => 'Tailored for your goals and industry.',
                            'body_ar' => 'مصممة لتناسب أهدافك ومجال عملك.',
                        ],
                        [
                            'title_en' => 'Measurable outcomes',
                            'title_ar' => 'نتائج قابلة للقياس',
                            'body_en' => 'Track learner progress and success.',
                            'body_ar' => 'تابع تقدّم المتعلّم ونجاحه بوضوح.',
                        ],
                        [
                            'title_en' => 'Trusted instructors',
                            'title_ar' => 'مدربون موثوقون',
                            'body_en' => 'Experienced instructors with real-world insight.',
                            'body_ar' => 'خبرات واقعية وأساليب تدريب فعّالة.',
                        ],
                        [
                            'title_en' => 'Support team',
                            'title_ar' => 'فريق دعم',
                            'body_en' => 'Dedicated support throughout the journey.',
                            'body_ar' => 'دعم مخصص طوال رحلة التعلم.',
                        ],
                    ],
                ],
                'sort_order' => 110,
            ],
            [
                'type' => 'corporate',
                'title' => 'Corporate training',
                'subtitle' => 'Upskill teams with flexible programs',
                'active' => false,
                'content' => [
                    'tag_en' => 'Corporate',
                    'tag_ar' => 'للشركات',
                    'title_en' => 'Corporate training',
                    'title_ar' => 'التدريب المؤسسي',
                    'subtitle_en' => 'Upskill teams with flexible programs',
                    'subtitle_ar' => 'طوّر مهارات الفرق عبر برامج مرنة',
                    'button_text_en' => 'Request info',
                    'button_text_ar' => 'طلب معلومات',
                    'button_url' => '/#contact',
                ],
                'sort_order' => 120,
            ],
            [
                'type' => 'testimonials',
                'title' => 'Testimonials',
                'active' => false,
                'subtitle' => 'What our learners say',
                'content' => [
                    'title_en' => 'Testimonials',
                    'title_ar' => 'آراء المتعلمين',
                    'subtitle_en' => 'What our learners say',
                    'subtitle_ar' => 'ماذا يقول المتعلمون',
                    'items' => [
                        [
                            'text_en' => 'Clear structure and practical projects.',
                            'text_ar' => 'تنظيم واضح ومشاريع عملية مفيدة.',
                            'name_en' => 'Sara Ahmed',
                            'name_ar' => 'سارة أحمد',
                            'role_en' => 'HR Manager',
                            'role_ar' => 'مدير الموارد البشرية',
                            'avatar' => null,
                        ],
                        [
                            'text_en' => 'The team loved the instructor-led sessions.',
                            'text_ar' => 'الفريق أعجب كثيرًا بالجلسات مع المدرب.',
                            'name_en' => 'Omar Ali',
                            'name_ar' => 'عمر علي',
                            'role_en' => 'Team Lead',
                            'role_ar' => 'قائد فريق',
                            'avatar' => null,
                        ],
                    ],
                ],
                'sort_order' => 130,
            ],
            [
                'type' => 'stats',
                'title' => null,
                'active' => false,
                'subtitle' => null,
                'content' => [
                    'items' => [
                        ['value' => '+3,200', 'label_en' => 'Students', 'label_ar' => 'متدربون'],
                        ['value' => '+180', 'label_en' => 'Entities', 'label_ar' => 'جهات'],
                        ['value' => '+120', 'label_en' => 'Programs', 'label_ar' => 'برامج'],
                    ],
                ],
                'sort_order' => 140,
            ],
            [
                'type' => 'lead_form',
                'active' => false,
                'title' => 'For more info',
                'subtitle' => 'Tell us what you need',
                'content' => [
                    'heading_en' => 'For more info',
                    'heading_ar' => 'لمزيد من المعلومات',
                    'description_en' => 'Share your needs and we will get back to you.',
                    'description_ar' => 'شاركنا احتياجاتك وسنعود إليك قريبًا.',
                    'submit_text_en' => 'Send request',
                    'submit_text_ar' => 'إرسال الطلب',
                    'note_en' => 'We respond within 24 hours.',
                    'note_ar' => 'نرد خلال 24 ساعة.',
                    'success_message_en' => 'Thanks! We will contact you soon.',
                    'success_message_ar' => 'شكرًا لك! سنتواصل معك قريبًا.',
                    'tag_en' => 'Contact',
                    'tag_ar' => 'تواصل',
                    'field_name_en' => 'Full name',
                    'field_name_ar' => 'الاسم الكامل',
                    'field_phone_en' => 'Phone number',
                    'field_phone_ar' => 'رقم الهاتف',
                    'field_course_en' => 'Course',
                    'field_course_ar' => 'الدورة',
                    'course_placeholder_en' => 'Select a course',
                    'course_placeholder_ar' => 'اختر دورة',
                    'field_message_en' => 'Message',
                    'field_message_ar' => 'الرسالة',
                ],
                'sort_order' => 150,
            ],
            [
                'type' => 'complaints',
                'title' => 'Complaints',
                'active' => false,
                'subtitle' => 'We are here to help',
                'content' => [
                    'tag_en' => 'Support',
                    'tag_ar' => 'الدعم',
                    'title_en' => 'Complaints',
                    'title_ar' => 'الشكاوى',
                    'subtitle_en' => 'We are here to help',
                    'subtitle_ar' => 'نحن هنا لمساعدتك',
                    'button_text_en' => 'Email us',
                    'button_text_ar' => 'راسلنا عبر البريد',
                    'button_url' => 'mailto:info@example.com',
                ],
                'sort_order' => 160,
            ],
        ];

        foreach ($homeSections as $sectionData) {
            $section = Section::create([
                'page_id' => $homePage->id,
                'type' => $sectionData['type'],
                'title' => $sectionData['title'],
                'subtitle' => $sectionData['subtitle'],
                'is_active' => $sectionData['active'] ?? true,
                'sort_order' => $sectionData['sort_order'],
            ]);
            $this->syncSectionFields($section, $sectionData['content']);
        }

        // Create About page with sections
        $aboutPage = Page::create([
            'title' => ['en' => 'About Us', 'ar' => 'حول'],
            'slug' => 'about-us',
            'body' => ['en' => 'Learn more about our mission', 'ar' => 'تعرف على مزيد حول رسالتنا'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        // About Hero Section
        $aboutHeroSection = Section::create([
            'page_id' => $aboutPage->id,
            'type' => 'hero',
            'title' => 'About Our Organization',
            'subtitle' => 'Building excellence in professional education',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->syncSectionFields($aboutHeroSection, [
            'layout' => 'about',
            'heading_en' => 'Transforming Professional Learning',
            'heading_ar' => 'تحويل التعلم المهني',
            'subheading_en' => 'Founded on the belief that quality education should be accessible to everyone, we provide comprehensive training programs designed for today\'s professionals.',
            'subheading_ar' => 'نؤمن بأن التعليم الجودة يجب أن يكون متاحاً للجميع، ونقدم برامج تدريبية شاملة مصممة للمحترفين اليوم.',
            'stats' => [
                ['value' => '15+', 'label_en' => 'Years Experience', 'label_ar' => 'سنوات من الخبرة'],
                ['value' => '50K+', 'label_en' => 'Happy Students', 'label_ar' => 'طلاب سعداء'],
                ['value' => '200+', 'label_en' => 'Expert Instructors', 'label_ar' => 'مدربون خبراء'],
                ['value' => '95%', 'label_en' => 'Success Rate', 'label_ar' => 'معدل النجاح'],
            ],
        ]);

        // About Story Section
        $storySection = Section::create([
            'page_id' => $aboutPage->id,
            'type' => 'story',
            'title' => 'Our Story',
            'subtitle' => 'A journey of excellence',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->syncSectionFields($storySection, [
            'heading_en' => 'How We Started',
            'heading_ar' => 'كيف بدأنا',
            'content_en' => 'Our journey began with a simple vision: to make professional education accessible and affordable for everyone. Today, we\'re proud to serve thousands of learners across the globe.',
            'content_ar' => 'بدأت رحلتنا برؤية بسيطة: جعل التعليم المهني متاحاً وبأسعار معقولة للجميع. اليوم، نفخر بخدمة آلاف المتعلمين حول العالم.',
            'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=600&q=80',
        ]);

        // Values Section
        $valuesSection = Section::create([
            'page_id' => $aboutPage->id,
            'type' => 'values',
            'title' => 'Our Values',
            'subtitle' => 'Guiding principles of our organization',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->syncSectionFields($valuesSection, [
            'items' => [
                [
                    'title_en' => 'Excellence',
                    'title_ar' => 'التميز',
                    'description_en' => 'We strive for excellence in every aspect of our work.',
                    'description_ar' => 'نسعى للتميز في كل جوانب عملنا.',
                ],
                [
                    'title_en' => 'Integrity',
                    'title_ar' => 'النزاهة',
                    'description_en' => 'We act with honesty and strong moral principles.',
                    'description_ar' => 'نتصرف بصدق والتزام بمبادئ أخلاقية قوية.',
                ],
                [
                    'title_en' => 'Innovation',
                    'title_ar' => 'الابتكار',
                    'description_en' => 'We embrace change and continuous improvement.',
                    'description_ar' => 'نتبنى التغيير والتحسين المستمر.',
                ],
                [
                    'title_en' => 'Impact',
                    'title_ar' => 'التأثير',
                    'description_en' => 'We create meaningful change in professionals\' lives.',
                    'description_ar' => 'نخلق تغييراً ذا مغزى في حياة المحترفين.',
                ],
            ],
        ]);

        // Create Contact page
        $contactPage = Page::create([
            'title' => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
            'slug' => 'contact-us',
            'body' => ['en' => 'Get in touch with us', 'ar' => 'تواصل معنا'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        // Contact sections
        $contactSections = [
            [
                'type' => 'contact_hero',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'heading_en' => 'Get in Touch for Questions, Support, or Collaboration Ideas',
                    'heading_ar' => 'تواصل معنا للاستفسارات أو الدعم أو أفكار التعاون',
                    'cards' => [
                        [
                            'title_en' => 'Visit Us',
                            'title_ar' => 'زورونا',
                            'body_en' => 'Riyadh - Ishbilia District - King Abdullah Road. Al Muhammadiyah Building - Third Floor',
                            'body_ar' => 'الرياض - حي إشبيليا - طريق الملك عبدالله. مبنى المحمدية - الدور الثالث',
                            'icon' => 'pin',
                        ],
                        [
                            'title_en' => 'Office Opening',
                            'title_ar' => 'ساعات العمل',
                            'body_en' => 'Saturday: 12:00 PM - 9:00 PM. Sunday to Wednesday: 10:00 AM - 7:00 PM. Thursday: 10:00 AM - 5:00 PM',
                            'body_ar' => 'السبت: 12:00 ظهرًا - 9:00 مساءً. من الأحد إلى الأربعاء: 10:00 صباحًا - 7:00 مساءً. الخميس: 10:00 صباحًا - 5:00 مساءً',
                            'icon' => 'clock',
                        ],
                        [
                            'title_en' => 'Chat with us',
                            'title_ar' => 'راسلنا',
                            'body_en' => 'contact@themiran.net',
                            'body_ar' => 'contact@themiran.net',
                            'icon' => 'chat',
                        ],
                        [
                            'title_en' => 'Call Us',
                            'title_ar' => 'اتصل بنا',
                            'body_en' => '0563068348',
                            'body_ar' => '0563068348',
                            'icon' => 'phone',
                        ],
                    ],
                ],
                'sort_order' => 10,
            ],
            [
                'type' => 'lead_form',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'layout' => 'contact',
                    'heading_en' => 'Get in Touch With Us',
                    'heading_ar' => 'تواصل معنا',
                    'description_en' => 'Get Insights on how we can grow your business.',
                    'description_ar' => 'احصل على أفكار حول كيف يمكننا دعم نمو أعمالك.',
                    'submit_text_en' => 'Send Message',
                    'submit_text_ar' => 'إرسال الرسالة',
                    'success_message_en' => 'Thanks! We will contact you soon.',
                    'success_message_ar' => 'شكرًا لك! سنتواصل معك قريبًا.',
                    'field_first_name_en' => 'First Name',
                    'field_first_name_ar' => 'الاسم الأول',
                    'field_last_name_en' => 'Last Name',
                    'field_last_name_ar' => 'اسم العائلة',
                    'field_email_en' => 'Email',
                    'field_email_ar' => 'البريد الإلكتروني',
                    'field_phone_en' => 'Phone Number',
                    'field_phone_ar' => 'رقم الهاتف',
                    'field_message_en' => 'Describe your problem here',
                    'field_message_ar' => 'اكتب رسالتك هنا',
                    'placeholder_first_name_en' => 'Insert your first name',
                    'placeholder_first_name_ar' => 'أدخل اسمك الأول',
                    'placeholder_last_name_en' => 'Insert your last name',
                    'placeholder_last_name_ar' => 'أدخل اسم العائلة',
                    'placeholder_email_en' => 'Insert your email',
                    'placeholder_email_ar' => 'أدخل بريدك الإلكتروني',
                    'placeholder_phone_en' => '2323xxxx',
                    'placeholder_phone_ar' => '2323xxxx',
                    'phone_prefix' => '+965',
                    'placeholder_message_en' => 'Type here...',
                    'placeholder_message_ar' => 'اكتب هنا...',
                    'image' => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=900&q=80',
                ],
                'sort_order' => 20,
            ],
        ];

        foreach ($contactSections as $sectionData) {
            $section = Section::create([
                'page_id' => $contactPage->id,
                'type' => $sectionData['type'],
                'title' => $sectionData['title'],
                'subtitle' => $sectionData['subtitle'],
                'is_active' => true,
                'sort_order' => $sectionData['sort_order'],
            ]);
            $this->syncSectionFields($section, $sectionData['content']);
        }

        // Create Complaints page
        $complaintsPage = Page::create([
            'title' => ['en' => 'Complaints', 'ar' => 'الشكاوى'],
            'slug' => 'complaints',
            'body' => ['en' => 'Share your concerns with us', 'ar' => 'شاركنا ملاحظاتك أو مشكلتك'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        // Complaints sections
        $complaintsSections = [
            [
                'type' => 'hero',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'layout' => 'complaint',
                    'heading_en' => 'Hi, How can we help you?',
                    'heading_ar' => 'مرحبًا، كيف يمكننا مساعدتك؟',
                ],
                'sort_order' => 10,
            ],
            [
                'type' => 'lead_form',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'layout' => 'complaint',
                    'heading_en' => "We're here to help you succeed",
                    'heading_ar' => 'نحن هنا لدعمك حتى تنجح',
                    'description_en' => 'Share your concerns with us. Fill in the form below to send us your complaint.',
                    'description_ar' => 'شاركنا ملاحظاتك أو مشكلتك. املأ النموذج أدناه لإرسال شكواك.',
                    'submit_text_en' => 'Send Message',
                    'submit_text_ar' => 'إرسال الرسالة',
                    'success_message_en' => 'Thanks! We will contact you soon.',
                    'success_message_ar' => 'شكرًا لك! سنتواصل معك قريبًا.',
                    'field_first_name_en' => 'First Name',
                    'field_first_name_ar' => 'الاسم الأول',
                    'field_last_name_en' => 'Last Name',
                    'field_last_name_ar' => 'اسم العائلة',
                    'field_email_en' => 'Email',
                    'field_email_ar' => 'البريد الإلكتروني',
                    'field_phone_en' => 'Phone Number',
                    'field_phone_ar' => 'رقم الهاتف',
                    'field_message_en' => 'Describe your problem here',
                    'field_message_ar' => 'اكتب تفاصيل المشكلة هنا',
                    'placeholder_first_name_en' => 'Insert your first name',
                    'placeholder_first_name_ar' => 'أدخل اسمك الأول',
                    'placeholder_last_name_en' => 'Insert your last name',
                    'placeholder_last_name_ar' => 'أدخل اسم العائلة',
                    'placeholder_email_en' => 'Insert your email',
                    'placeholder_email_ar' => 'أدخل بريدك الإلكتروني',
                    'placeholder_phone_en' => 'Insert your phone number',
                    'placeholder_phone_ar' => 'أدخل رقم هاتفك',
                    'placeholder_message_en' => 'Type here...',
                    'placeholder_message_ar' => 'اكتب هنا...',
                    'image' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
                ],
                'sort_order' => 20,
            ],
        ];

        foreach ($complaintsSections as $sectionData) {
            $section = Section::create([
                'page_id' => $complaintsPage->id,
                'type' => $sectionData['type'],
                'title' => $sectionData['title'],
                'subtitle' => $sectionData['subtitle'],
                'is_active' => true,
                'sort_order' => $sectionData['sort_order'],
            ]);
            $this->syncSectionFields($section, $sectionData['content']);
        }

        // Create About page
        $aboutPage = Page::create([
            'title' => ['en' => 'About Us', 'ar' => 'من نحن'],
            'slug' => 'about',
            'body' => ['en' => 'Learn more about our mission', 'ar' => 'تعرف على مزيد حول رسالتنا'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        // About sections
        $aboutSections = [
            [
                'type' => 'hero',
                'title' => null,
                'subtitle' => null,
                'content' => [
                    'layout' => 'about',
                    'heading_en' => 'We share knowledge with the world',
                    'heading_ar' => 'نشارك المعرفة مع العالم',
                    'subheading_en' => 'Empowering learners with practical, accredited programs built for personal and professional growth.',
                    'subheading_ar' => 'نمكّن المتعلمين عبر برامج عملية ومعتمدة صُممت للنمو الشخصي والمهني.',
                    'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80',
                    'stats' => [
                        ['value' => '500+', 'label_en' => 'Amazing Course', 'label_ar' => 'دورة مميزة'],
                        ['value' => '100+', 'label_en' => 'Professional Mentor', 'label_ar' => 'مدرّب محترف'],
                        ['value' => '90k+', 'label_en' => 'Good Reviews', 'label_ar' => 'تقييمات رائعة'],
                    ],
                ],
                'sort_order' => 10,
            ],
            [
                'type' => 'values',
                'title' => 'Improving lives through learning',
                'subtitle' => 'Whether you want to learn or to share what you know, you have come to the right place. As a global destination for online learning, we connect people through knowledge.',
                'content' => [
                    'title_en' => 'Improving lives through learning',
                    'title_ar' => 'نُحسّن حياة الناس بالتعلّم',
                    'subtitle_en' => 'Whether you want to learn or to share what you know, you have come to the right place. As a global destination for online learning, we connect people through knowledge.',
                    'subtitle_ar' => 'سواء كنت تريد أن تتعلّم أو تشارك ما تعرفه، فأنت في المكان الصحيح. كوجهة عالمية للتعلّم عبر الإنترنت، نربط الناس بالمعرفة.',
                    'cards' => [
                        [
                            'title_en' => 'Vision',
                            'title_ar' => 'رؤيتنا',
                            'body_en' => 'Our vision is to lead educational innovation and empower individuals to unlock their full potential.',
                            'body_ar' => 'رؤيتنا أن نقود الابتكار التعليمي ونمكّن الأفراد من تحقيق كامل إمكاناتهم.',
                        ],
                        [
                            'title_en' => 'Mission',
                            'title_ar' => 'رسالتنا',
                            'body_en' => 'Our mission is to deliver accessible, high-quality education that bridges knowledge and opportunity.',
                            'body_ar' => 'رسالتنا تقديم تعليم عالي الجودة وسهل الوصول يربط بين المعرفة والفرص.',
                        ],
                    ],
                ],
                'sort_order' => 20,
            ],
            [
                'type' => 'story',
                'title' => 'About Us',
                'subtitle' => 'Maran is built on a foundation of diverse experts who bring unique perspectives, creating a culture of growth.',
                'content' => [
                    'title_en' => 'About Us',
                    'title_ar' => 'من نحن',
                    'subtitle_en' => 'Maran is built on a foundation of diverse experts who bring unique perspectives, creating a culture of growth.',
                    'subtitle_ar' => 'تم تأسيس "مران" على خبرات متنوعة تقدم رؤى مختلفة، لتصنع ثقافة تعلّم ونمو مستمر.',
                    'blocks' => [
                        [
                            'body_en' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lectus in ipsum condimentum tellus nisl quis tellus faucibus bibendum. Elit ornare morbi vitae ultrices. Ullamcorper tristique diam scelerisque arcu nam urna, sollicitudin orci, pharetra.',
                            'body_ar' => 'هذا نص تجريبي يمكن استبداله لاحقًا بمحتوى تعريفي واضح عن المؤسسة ورسالتها وقيمها وما تقدّمه للمتعلمين.',
                            'image' => 'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=800&q=80',
                            'alignment' => 'image-left',
                        ],
                        [
                            'body_en' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Lectus in ipsum condimentum tellus nisl quis tellus faucibus bibendum. Elit ornare morbi vitae ultrices. Ullamcorper tristique diam scelerisque arcu nam urna, sollicitudin orci, pharetra.',
                            'body_ar' => 'نركّز على تقديم برامج تدريب عملية تُحوّل المعرفة إلى مهارة، وتساعد الأفراد والفرق على تحقيق نتائج ملموسة في العمل.',
                            'image' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80',
                            'alignment' => 'image-right',
                        ],
                    ],
                ],
                'sort_order' => 30,
            ],
            [
                'type' => 'partners',
                'title' => 'Our Trusted Partners',
                'subtitle' => null,
                'content' => [
                    'title_en' => 'Our Trusted Partners',
                    'title_ar' => 'شركاؤنا الموثوقون',
                    'layout' => 'grid',
                    'logos' => [
                        'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png',
                        'https://themiran.net/wp-content/uploads/2023/11/New_Project-removebg-preview-e1699446752333.png',
                        'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg',
                        'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=400&q=80',
                        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=400&q=80',
                        'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80',
                    ],
                ],
                'sort_order' => 40,
            ],
        ];

        foreach ($aboutSections as $sectionData) {
            $section = Section::create([
                'page_id' => $aboutPage->id,
                'type' => $sectionData['type'],
                'title' => $sectionData['title'],
                'subtitle' => $sectionData['subtitle'],
                'is_active' => true,
                'sort_order' => $sectionData['sort_order'],
            ]);
            $this->syncSectionFields($section, $sectionData['content']);
        }

        Page::create([
            'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
            'slug' => 'privacy-policy',
            'body' => ['en' => 'Our privacy policy outlines how we collect and use your personal information.', 'ar' => 'توضح سياستنا بشأن الخصوصية كيفية جمعنا واستخدامنا لمعلوماتك الشخصية.'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);

        // Create Terms of Service page
        Page::create([
            'title' => ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة'],
            'slug' => 'terms-of-service',
            'body' => ['en' => 'Please read our terms of service carefully before using our platform.', 'ar' => 'يرجى قراءة شروط الخدمة الخاصة بنا بعناية قبل استخدام منصتنا.'],
            'template' => 'default',
            'is_published' => true,
            'is_active' => true,
        ]);
    }

    private function seedMenus(): void
    {
        // Create header menu
        $headerMenu = Menu::create([
            'name' => 'Header Menu',
            'location' => 'header',
            'is_active' => true,
        ]);

        // Get pages for linking
        $homePage = Page::where('slug', 'home')->first();
        $aboutPage = Page::where('slug', 'about')->first();
        $contactPage = Page::where('slug', 'contact-us')->first();
        $complaintsPage = Page::where('slug', 'complaints')->first();

        // Add header menu items
        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'Home', 'ar' => 'الرئيسية'],
            'type' => 'page',
            'page_id' => $homePage?->id,
            'url' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'About', 'ar' => 'من نحن'],
            'type' => 'page',
            'page_id' => $aboutPage?->id,
            'url' => null,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'Blogs', 'ar' => 'المدونه'],
            'type' => 'custom',
            'page_id' => null,
            'url' => '/blog',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'Courses', 'ar' => 'الدورات'],
            'type' => 'custom',
            'page_id' => null,
            'url' => '/courses',
            'sort_order' => 4,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'Contact Request', 'ar' => 'طلب تواصل'],
            'type' => 'page',
            'page_id' => $contactPage?->id,
            'url' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'Corporate Training', 'ar' => 'التدريب المؤسسي'],
            'type' => 'custom',
            'page_id' => null,
            'url' => '/#corporate',
            'sort_order' => 6,
            'is_active' => true,
        ]);

        MenuItem::create([
            'menu_id' => $headerMenu->id,
            'label' => ['en' => 'Complaints', 'ar' => 'الشكاوى'],
            'type' => 'page',
            'page_id' => $complaintsPage?->id,
            'url' => null,
            'sort_order' => 7,
            'is_active' => true,
        ]);
    }

    private function syncSectionFields(Section $section, array $content): void
    {
        $fields = SectionFieldNormalizer::extractFields($content);

        foreach ($fields as $key => $payload) {
            $section->setField($key, $payload['value'], $payload['type']);
        }
    }

    private function outputSummary($discounts): void
    {
        $discountedCourses = Course::query()
            ->whereHas('discounts', fn ($query) => $query->where('is_active', true))
            ->count();

        $lessonTypes = Lesson::query()->select('type')->distinct()->pluck('type')->all();
        $missingTypes = collect([LessonType::Text, LessonType::Video, LessonType::Pdf])
            ->reject(fn ($type) => in_array($type->value, $lessonTypes, true))
            ->values();

        $this->command?->info('# Demo Seed Summary');
        $this->command?->info('Users: '.User::count());
        $this->command?->info('Courses: '.Course::count());
        $this->command?->info('Groups: '.CourseGroup::count());
        $this->command?->info('Lessons: '.Lesson::count());
        $this->command?->info('Attachments: '.CourseAttachment::count());
        $this->command?->info('Pages: '.Page::count());
        $this->command?->info('Menus: '.Menu::count());
        $this->command?->info('Discounted courses: '.$discountedCourses);

        if ($discountedCourses < 1) {
            $this->command?->warn('Check: No active discounted course found.');
        }

        if ($missingTypes->isNotEmpty()) {
            $this->command?->warn('Check: Missing lesson types: '.$missingTypes->implode(', '));
        }

        if (CourseAttachment::count() < 1) {
            $this->command?->warn('Check: No attachments found.');
        }
    }
}
