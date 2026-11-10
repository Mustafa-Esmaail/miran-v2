<?php

namespace Database\Seeders;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseTemplate;
use App\Enums\DeliveryType;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo bootcamp courses for both bootcamp designs (template 1 and template 2).
 *
 * Safe to re-run: courses, the category and the group are matched and updated,
 * modules are matched by their order, and the group's sessions are rebuilt.
 * The group has no price of its own — the course price is used.
 *
 * php artisan db:seed --class=BootcampSeeder
 */
class BootcampSeeder extends Seeder
{
    private const PRICE = 1450;

    public function run(): void
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'artificial-intelligence'],
            ['name' => ['ar' => 'الذكاء الاصطناعي', 'en' => 'Artificial Intelligence'], 'is_active' => true],
        );

        $designs = [
            'ai-bootcamp' => [
                'template' => CourseTemplate::Bootcamp,
                'title' => ['ar' => 'معسكر الذكاء الاصطناعي التطبيقي', 'en' => 'Applied AI Bootcamp'],
                'content' => $this->sharedContent() + $this->designOneContent(),
            ],
            'maaskr-althkaaa-alastnaaay-alttbyky-ai-fundamentals-bootcamp' => [
                'template' => CourseTemplate::BootcampV2,
                'title' => ['ar' => 'معسكر الذكاء الاصطناعي التطبيقي لبيئة العمل', 'en' => 'Applied AI Bootcamp for the Workplace'],
                'content' => $this->sharedContent(),
            ],
        ];

        foreach ($designs as $slug => $design) {
            $course = Course::query()->updateOrCreate(['slug' => $slug], [
                'category_id' => $category->id,
                'title' => $design['title'],
                'summary' => [
                    'ar' => '<p>8 محاور تطبيقية مباشرة مع المدرب، كل محور تطلع منه بشيء جاهز تستخدمه في شغلك.</p>',
                    'en' => '<p>8 hands-on modules, live with the trainer. Every module leaves you with something ready to use at work.</p>',
                ],
                'price' => self::PRICE,
                'is_free' => false,
                'status' => 'published',
                'delivery_type' => DeliveryType::Online->value,
                'duration_hours' => 36,
                'days_count' => 9,
                'template' => $design['template'],
                'template_content' => $design['content'],
            ]);

            $group = $this->seedGroup($course);
            $modules = $this->seedModules($course, $group);
            $this->seedSessions($group, $modules);

            $this->command?->info("Bootcamp \"{$slug}\" ({$design['template']->value}) seeded: /courses/{$slug}");
        }
    }

    /**
     * One open cohort starting on the first Saturday at least two weeks from now.
     */
    private function seedGroup(Course $course): CourseGroup
    {
        $start = $this->firstSessionAt();

        $group = $course->groups()->oldest('id')->first() ?? new CourseGroup(['course_id' => $course->id]);

        $group->fill([
            'title' => ['ar' => 'الدفعة القادمة', 'en' => 'Next cohort'],
            'start_at' => $start,
            'end_at' => $start->copy()->addDays(18)->setTime(22, 0),
            'enrollment_deadline_at' => $start->copy()->subDay()->endOfDay(),
            'capacity' => 30,
            'status' => CourseGroupStatus::Open,
        ])->save();

        return $group;
    }

    /**
     * @return array<int, CourseCurriculumSection> keyed by module order (1–8)
     */
    private function seedModules(Course $course, CourseGroup $group): array
    {
        $modules = [];

        foreach ($this->modules() as $index => $module) {
            $order = $index + 1;

            $modules[$order] = CourseCurriculumSection::query()->updateOrCreate(
                ['course_id' => $course->id, 'sort_order' => $order],
                [
                    'course_group_id' => $group->id,
                    'title' => $module['title'],
                    'description' => $module['description'],
                    'week' => $module['week'],
                    'badge' => $module['badge'] ?? null,
                    'outcome' => $module['outcome'],
                    'bullets' => array_map(fn (array $bullet): array => ['text' => $bullet], $module['bullets']),
                    'is_featured' => $module['featured'] ?? false,
                    'is_capstone' => $module['capstone'] ?? false,
                ],
            );
        }

        return $modules;
    }

    /**
     * Saturday, Monday and Wednesday, 6–10 pm, for three weeks (9 sessions).
     *
     * @param  array<int, CourseCurriculumSection>  $modules
     */
    private function seedSessions(CourseGroup $group, array $modules): void
    {
        $group->sessions()->delete();

        $start = $this->firstSessionAt();
        $plan = [
            // [week, day offset from Saturday, module, title]
            [1, 0, 1, 'المدخل والتوجيه الاحترافي'],
            [1, 2, 3, 'الصور والفيديو'],
            [1, 4, 4, 'العروض والتقارير المرئية'],
            [2, 0, 5, 'المساعد الذكي المخصص'],
            [2, 2, 6, 'تحليل البيانات'],
            [2, 4, 7, 'أتمتة المهام'],
            [3, 0, 7, 'أتمتة المهام (تطبيق متقدم)'],
            [3, 2, 8, 'مشروع التخرج: السكرتير الافتراضي'],
            [3, 4, 8, 'مشروع التخرج: العرض النهائي'],
        ];

        foreach ($plan as [$week, $dayOffset, $module, $title]) {
            $group->sessions()->create([
                'course_curriculum_section_id' => $modules[$module]->id,
                'title' => $title,
                'start_at' => $start->copy()->addDays(($week - 1) * 7 + $dayOffset),
                'duration_minutes' => 240,
            ]);
        }
    }

    private function firstSessionAt(): Carbon
    {
        return now()->addDays(14)->next(CarbonInterface::SATURDAY)->setTime(18, 0);
    }

    /**
     * Content both designs show (pillars, method, audience, FAQ, final CTA).
     *
     * @return array<string, mixed>
     */
    private function sharedContent(): array
    {
        return [
            'pillars' => [
                $this->item(['title' => ['تأسّس', 'Foundations'], 'description' => ['تفهم كيف يفكر الذكاء الاصطناعي وتتقن توجيهه', 'Understand how AI thinks and master prompting it']]),
                $this->item(['title' => ['تنتج', 'Produce'], 'description' => ['صور، فيديو، عروض، وتقارير جاهزة', 'Images, video, presentations and ready-made reports']]),
                $this->item(['title' => ['تخصّص', 'Specialize'], 'description' => ['مساعد شخصي تبنيه لمهمة معينة في شغلك، وتحليل لبياناتك', 'A personal assistant built for one task at work, plus analysis of your data']]),
                $this->item(['title' => ['تؤتمت', 'Automate'], 'description' => ['مهامك المتكررة تشتغل لحالها، كل واحد حسب احتياجه', 'Your repetitive tasks run on their own, tailored to your needs']]),
            ],
            'method_steps' => [
                $this->item(['title' => ['تدريب مباشر مع المدرب', 'Live training with the trainer'], 'description' => ['جلسة تطبيقية مباشرة لكل محور', 'A live, hands-on session for each module']]),
                $this->item(['title' => ['تطبّق على مهام شغلك', 'Apply it to your own work'], 'description' => ['بين اللقاءات تطبّق على مهام حقيقية من شغلك', 'Between sessions you practice on real tasks from your job']]),
                $this->item(['title' => ['المدرب يراجع ويصحح', 'The trainer reviews and corrects'], 'description' => ['ملاحظات مباشرة على شغلك قبل ما نكمل', 'Direct feedback on your work before we move on']]),
                $this->item(['title' => ['نكمل المحور اللي بعده', 'On to the next module'], 'description' => ['كل محور يبني على اللي قبله', 'Every module builds on the one before']]),
            ],
            'method_note' => $this->text(
                "بين كل لقاء ولقاء عندك وقت تطبّق فيه على مهام من شغلك أنت، والمدرب يراجع لك ويصحح قبل ما نكمل.\nوكل الجلسات مسجّلة ترجع لها، ومعك قوالب وملفات تستلمها ومجتمع تسأل فيه.",
                "Between sessions you have time to practice on tasks from your own work, and the trainer reviews and corrects it before we continue.\nEvery session is recorded, and you get templates, files and a community to ask questions in.",
            ),
            'audience' => [
                $this->item(['icon' => ['💼', '💼'], 'title' => ['موظف', 'Employee'], 'description' => ['وتبي تنجز شغلك أسرع وبجودة أعلى', 'who wants to get work done faster and better']]),
                $this->item(['icon' => ['👥', '👥'], 'title' => ['مدير فريق', 'Team manager'], 'description' => ['وتبي ترفع إنتاجية فريقك', 'who wants to raise the team\'s productivity']]),
                $this->item(['icon' => ['🚀', '🚀'], 'title' => ['صاحب مشروع', 'Business owner'], 'description' => ['وتسوي كل شي بنفسك', 'who does everything themselves']]),
                $this->item(['icon' => ['🎯', '🎯'], 'title' => ['باحث عن عمل', 'Job seeker'], 'description' => ['وتبي مهارة مطلوبة تميّزك عن غيرك من المتقدمين', 'who wants an in-demand skill that sets them apart']]),
            ],
            'not_for' => $this->text(
                'تبي تتعلم برمجة النماذج وبناءها تقنيًا. المعسكر تطبيقي للاستخدام في الشغل بشكل عملي سريع، مو للمبرمجين المتخصصين.',
                'You want to learn to program and build AI models. This bootcamp is about practical use at work, not for specialist programmers.',
            ),
            'faqs' => [
                $this->item(['question' => ['أحتاج خبرة تقنية أو برمجة؟', 'Do I need technical or programming experience?'], 'answer' => ['لا. كل المحاور بدون برمجة، ونبدأ من الصفر.', 'No. Every module is no-code, and we start from scratch.']]),
                $this->item(['question' => ['ما عمري استخدمت الذكاء الاصطناعي، يناسبني؟', 'I have never used AI. Is it for me?'], 'answer' => ['نعم، أول محورين يأسسونك قبل ما ندخل في التطبيقات المتقدمة.', 'Yes, the first two modules build your foundation before the advanced applications.']]),
                $this->item(['question' => ['إذا فاتتني جلسة؟', 'What if I miss a session?'], 'answer' => ['كل الجلسات مسجلة وترجع لها متى ما تبي.', 'Every session is recorded, so you can watch it whenever you like.']]),
                $this->item(['question' => ['كيف يكون التطبيق؟', 'How does the practice work?'], 'answer' => ['بين كل لقاء ولقاء عندك أيام تطبق فيها على مهام من شغلك، والمدرب يراجع شغلك في اللقاء التالي.', 'Between sessions you have days to practice on tasks from your job, and the trainer reviews your work in the next session.']]),
            ],
            'final_cta_title' => $this->text('شغلك بعد المعسكر غير شغلك قبله', 'Your work after the bootcamp is not the same as before'),
            'final_cta_text' => $this->text('8 محاور، وكل محور تطلع منه بشيء جاهز. الباقي عليك تحجز.', '8 modules, each leaving you with something ready to use. All that is left is to book.'),
        ];
    }

    /**
     * Sections only design 1 shows (industries marquee, projects, before/after, pricing).
     *
     * @return array<string, mixed>
     */
    private function designOneContent(): array
    {
        return [
            'tagline' => $this->text('معسكر الذكاء الاصطناعي التطبيقي لبيئة العمل', 'The applied AI bootcamp for the workplace'),
            'industries' => array_map(
                fn (array $pair): array => $this->item(['text' => $pair]),
                [['الموارد البشرية', 'Human resources'], ['التسويق', 'Marketing'], ['المالية', 'Finance'], ['المبيعات', 'Sales'], ['خدمة العملاء', 'Customer service'], ['إدارة المشاريع', 'Project management']],
            ),
            'projects' => [
                $this->item(['title' => ['فيديو قصير', 'A short film'], 'description' => ['فيديو قصير كامل من صنعك، من الفكرة إلى المونتاج', 'A complete short film of your own, from idea to edit'], 'tag' => ['صور وفيديو', 'Image & video'], 'module_label' => ['المحور 3', 'Module 3']]),
                $this->item(['title' => ['مساعد ذكي مخصص', 'A custom AI assistant'], 'description' => ['مساعد شغّال له وظيفة محددة بقيود رسمتها أنت', 'A working assistant with one job and rules you set'], 'tag' => ['تخصيص', 'Specialization'], 'module_label' => ['المحور 5', 'Module 5']]),
                $this->item(['title' => ['السكرتير الافتراضي', 'The virtual secretary'], 'description' => ['يفرز بريدك ويطلّع لك المهام والقرارات من اجتماعاتك', 'Sorts your email and pulls tasks and decisions out of your meetings'], 'tag' => ['أتمتة', 'Automation'], 'module_label' => ['المحور 8', 'Module 8']]),
            ],
            'before_items' => array_map(
                fn (array $pair): array => $this->item(['text' => $pair]),
                [['ساعات في تقارير وعروض متكررة', 'Hours on repetitive reports and slides'], ['نتائج عامة من أوامر ضعيفة', 'Generic results from weak prompts'], ['مهام يدوية كل يوم', 'Manual tasks every day']],
            ),
            'after_items' => array_map(
                fn (array $pair): array => $this->item(['text' => $pair]),
                [['عرض كامل في جلسة واحدة', 'A full presentation in one session'], ['نتائج دقيقة من أول مرة', 'Accurate results the first time'], ['مهام تشتغل لحالها', 'Tasks that run on their own']],
            ),
            'offer_label' => $this->text('سعر الإطلاق', 'Launch price'),
            'included_features' => array_map(
                fn (array $pair): array => $this->item(['text' => $pair]),
                [['9 لقاءات مباشرة (36 ساعة)', '9 live sessions (36 hours)'], ['تسجيلات كل الجلسات', 'Recordings of every session'], ['قوالب وملفات جاهزة', 'Ready-made templates and files'], ['مراجعة المدرب لتطبيقاتك', 'Trainer review of your work'], ['شهادة إتمام', 'Completion certificate']],
            ),
        ];
    }

    /**
     * The 8 modules from the bootcamp design.
     *
     * @return array<int, array<string, mixed>>
     */
    private function modules(): array
    {
        return [
            [
                'week' => 1,
                'title' => $this->text('مدخل إلى الذكاء الاصطناعي', 'Introduction to AI'),
                'description' => $this->html('تفهم كيف يفكر الذكاء الاصطناعي، وتعرف أي نموذج تروح له في أي مهمة بدل ما تجرب عشوائي.', 'Understand how AI thinks, and know which model to use for which task instead of guessing.'),
                'outcome' => $this->text('معرفة واضحة بأي نموذج يناسب أي مهمة مستقبلًا', 'A clear sense of which model suits which task'),
                'bullets' => [
                    $this->text('نبدأ من الصفر بدون تعقيد، وتطرح سؤال واحد على ثلاثة نماذج مختلفة', 'We start from scratch and ask one question to three different models'),
                    $this->text('تشوف بعينك مين أدق ومين أسلوبه أحسن ومين يعطيك مصادر', 'You see which is most accurate, which writes best and which cites sources'),
                    $this->text('نقارن بينها ونوضح لك مزايا كل نموذج ومتى تستخدمه', 'We compare them and explain when to use each model'),
                ],
            ],
            [
                'week' => 1,
                'featured' => true,
                'badge' => $this->text('الأهم', 'Most important'),
                'title' => $this->text('التوجيه الاحترافي', 'Professional prompting'),
                'description' => $this->html('أهم مهارة في المعسكر. تكتب الأمر بطريقة تخلي النتيجة دقيقة ومطابقة لشغلك من أول مرة.', 'The key skill of the bootcamp: write prompts that give accurate, on-target results the first time.'),
                'outcome' => $this->text('قوالب جاهزة تبني عليها أي أمر بعدين', 'Ready-made templates to build any prompt on'),
                'bullets' => [
                    $this->text('ناخذ أمر ضعيف من النوع اللي يكتبه أغلب الناس ويستغربون ليش الجواب عام', 'We take a weak prompt of the kind most people write'),
                    $this->text('نشرّحه سوا لين يصير احترافي', 'We take it apart together until it is professional'),
                    $this->text('تشوف الفرق في النتيجة قدامك', 'You see the difference in the result right away'),
                ],
            ],
            [
                'week' => 1,
                'title' => $this->text('الصور والفيديو', 'Images and video'),
                'description' => $this->html('تنتج صور احترافية وفيديوهات قصيرة بنفسك، بدون خبرة تصميم ولا مونتاج.', 'Produce professional images and short videos yourself, with no design or editing experience.'),
                'outcome' => $this->text('فيديو قصير كامل من صنعك', 'A complete short film of your own'),
                'bullets' => [
                    $this->text('نشتغل على أدوات احترافية تطلّع صور عالية الجودة وفيديوهات بمستوى سينمائي', 'We work with professional tools for high-quality images and cinematic video'),
                    $this->text('تتدرب تكتب أمر يطلّع الصورة اللي في بالك مو شي قريب منها', 'You practice prompts that produce exactly the image you have in mind'),
                    $this->text('نطبق نفس الفكرة على الفيديو: تسويقي، تعليمي، أو مهني', 'We apply the same idea to video: marketing, training or professional'),
                ],
            ],
            [
                'week' => 1,
                'title' => $this->text('العروض والتقارير المرئية', 'Presentations and visual reports'),
                'description' => $this->html('الشغل اللي كان ياخذ منك أيام يخلص في جلسة واحدة: عرض أو تقرير مرئي أو إنفوجرافيك كامل.', 'Work that used to take days is done in one session: a presentation, visual report or infographic.'),
                'outcome' => $this->text('عرض تقديمي جاهز للعرض', 'A presentation ready to deliver'),
                'bullets' => [
                    $this->text('تعطيه الفكرة أو النص أو الأرقام', 'You give it the idea, the text or the numbers'),
                    $this->text('هو يخطط ويصمم، وأنت توجّه', 'It plans and designs while you direct'),
                    $this->text('تراجع المحتوى وتحسّن التصميم، عشان يطلع شغلك أنت مو شغل آلة واضح', 'You review the content and refine the design so it is clearly your work'),
                ],
            ],
            [
                'week' => 2,
                'title' => $this->text('المساعد الذكي المخصص', 'The custom AI assistant'),
                'description' => $this->html('موظف افتراضي لمهمة محددة في شغلك، مثل مدقق يراجع تقارير فريقك على لوائح جهتك. تعلّمه مرة وحدة وينفذ بدون ما تشرح له كل مرة، وبدون برمجة.', 'A virtual employee for one task at work, like a reviewer that checks reports against your policies. Teach it once, no code needed.'),
                'outcome' => $this->text('مساعد شغّال، له وظيفة محددة بقيود رسمتها أنت', 'A working assistant with one job and rules you set'),
                'bullets' => [
                    $this->text('تحدد المهمة والقيود اللي يشتغل عليها المساعد', 'You define the task and the rules the assistant follows'),
                    $this->text('تزوده بملفات ومرجعيات جهتك', 'You give it your organization\'s files and references'),
                    $this->text('تجربه على حالات حقيقية وتعدّل لين يصير دقيق', 'You test it on real cases and adjust until it is accurate'),
                ],
            ],
            [
                'week' => 2,
                'title' => $this->text('تحليل البيانات', 'Data analysis'),
                'description' => $this->html('تقرأ الجداول الضخمة وتطلع منها بخلاصات ورسوم بيانية جاهزة للعرض، بدون ما تكتب معادلة وحدة.', 'Read large spreadsheets and get summaries and charts ready to present, without writing a single formula.'),
                'outcome' => $this->text('تقرير فيه مؤشرات واقتراحات', 'A report with indicators and recommendations'),
                'bullets' => [
                    $this->text('ترفع ملف بيانات حقيقي من شغلك', 'You upload a real data file from your work'),
                    $this->text('تسأل عنه بلغتك وتطلع الخلاصات', 'You ask about it in plain language and get the key findings'),
                    $this->text('تحوّل النتائج إلى رسوم بيانية وتقرير جاهز', 'You turn the results into charts and a ready report'),
                ],
            ],
            [
                'week' => 2,
                'title' => $this->text('أتمتة المهام', 'Task automation'),
                'description' => $this->html('تحوّل مهمة متكررة تاكل وقتك كل يوم إلى عملية تشتغل تلقائيًا بدون ما تلمسها، وبدون أي برمجة.', 'Turn a repetitive daily task into a process that runs automatically, with no code.'),
                'outcome' => $this->text('مهمة مؤتمتة شغّالة فعلًا', 'An automated task that actually runs'),
                'bullets' => [
                    $this->text('تختار مهمة متكررة من شغلك', 'You pick a repetitive task from your work'),
                    $this->text('تبني خطواتها في أداة أتمتة بدون برمجة', 'You build its steps in a no-code automation tool'),
                    $this->text('تشغّلها وتتأكد إنها تمشي لحالها', 'You run it and make sure it works on its own'),
                ],
            ],
            [
                'week' => 3,
                'capstone' => true,
                'title' => $this->text('مشروع التخرج: السكرتير الافتراضي', 'Capstone: the virtual secretary'),
                'description' => $this->html('تجمع كل اللي تعلمته في مشروع واحد: سكرتير افتراضي يفرز بريدك، يحضر اجتماعاتك الأونلاين، ويطلّع لك المهام والقرارات جاهزة. وبنفس الأساس تبني أي فكرة مشابهة تخدم شغلك لاحقًا.', 'Bring it all together in one project: a virtual secretary that sorts your email, attends your online meetings and hands you the tasks and decisions.'),
                'outcome' => $this->text('مشروع تخرج شغّال، وطريقة تطبقها على أي نظام ثاني تحتاجه', 'A working capstone, and a method you can reuse for any other system'),
                'bullets' => [
                    $this->text('تصمم سير عمل السكرتير من البريد إلى المهام', 'You design the secretary\'s workflow from email to tasks'),
                    $this->text('تربطه بأدواتك اليومية', 'You connect it to your daily tools'),
                    $this->text('تعرضه في الجلسة الأخيرة وتاخذ ملاحظات المدرب', 'You present it in the final session and get the trainer\'s feedback'),
                ],
            ],
        ];
    }

    /**
     * Builds a repeater row: each field given as [ar, en].
     *
     * @param  array<string, array{0: string, 1: string}>  $fields
     * @return array<string, array{ar: string, en: string}>
     */
    private function item(array $fields): array
    {
        return array_map(fn (array $pair): array => $this->text($pair[0], $pair[1]), $fields);
    }

    /**
     * @return array{ar: string, en: string}
     */
    private function text(string $ar, string $en): array
    {
        return ['ar' => $ar, 'en' => $en];
    }

    /**
     * @return array{ar: string, en: string}
     */
    private function html(string $ar, string $en): array
    {
        return ['ar' => '<p>'.$ar.'</p>', 'en' => '<p>'.$en.'</p>'];
    }
}
