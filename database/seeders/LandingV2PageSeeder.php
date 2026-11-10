<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Page;
use App\Models\Section;
use App\Models\ServiceRequestType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Creates the PMP online landing page on the "landing-v2" template, filled
 * with the content of the approved design (landing-v2.html, 2026-10-26).
 * The bundled public/img/landing-v2 images are copied to S3 so the section
 * editor's FileUpload fields can load (and keep) them.
 *
 * php artisan db:seed --class=LandingV2PageSeeder
 */
class LandingV2PageSeeder extends Seeder
{
    protected const PAGE_SLUG = 'pmp-online-v2';

    protected const COURSE_SLUG = 'pmp-online';

    public function run(): void
    {
        $course = Course::query()->where('slug', static::COURSE_SLUG)->first();

        if (! $course) {
            $this->command?->warn('Course ['.static::COURSE_SLUG.'] not found — the page is created without a linked course.');
        }

        $page = Page::query()->updateOrCreate(
            ['slug' => static::PAGE_SLUG],
            [
                ...$this->pageAttributes(),
                'landable_type' => $course ? Course::class : null,
                'landable_id' => $course?->id,
                'is_published' => true,
                'is_active' => true,
            ]
        );

        if ($page->sections()->exists()) {
            $this->command?->info('Page ['.static::PAGE_SLUG.'] already has sections — left untouched.');

            return;
        }

        $this->seedSections($page);

        $this->command?->info('Created landing page ['.static::PAGE_SLUG.'].');
    }

    /**
     * @return array{title: array<string, string>, template: string, meta_title: array<string, string>, meta_description: array<string, string>}
     */
    protected function pageAttributes(): array
    {
        return [
            'title' => ['ar' => 'دورة PMP عن بعد', 'en' => 'PMP Online Course'],
            'template' => 'landing-v2',
            'meta_title' => ['ar' => 'دورة PMP عن بعد | مركز مران للتدريب', 'en' => 'PMP Online Course | Miran Training Center'],
            'meta_description' => [
                'ar' => 'دورة إدارة المشاريع الاحترافية PMP عن بعد من مركز مران للتدريب: 35 ساعة، ست أمسيات، بـ1,199 ريال. سجّل جوالك وسيتصل بك مستشار التدريب.',
                'en' => 'Online PMP course by Miran Training Center: 35 hours over six evenings for SAR 1,199. Leave your number and a training advisor will call you.',
            ],
        ];
    }

    protected function seedSections(Page $page): void
    {
        $brands = $this->section($page, 'landing_v2_brands', 0);
        $brands->setField('brands', ['value' => [
            ['image' => $this->image('tvtc.png'), 'alt' => 'المؤسسة العامة للتدريب التقني والمهني', 'caption_ar' => 'مرخّصون', 'caption_en' => 'Licensed'],
            ['image' => $this->image('nelc.png'), 'alt' => 'المركز الوطني للتعليم الإلكتروني', 'caption_ar' => 'مرخّصون', 'caption_en' => 'Licensed'],
            ['image' => $this->image('pmi.png'), 'alt' => 'PMI', 'caption_ar' => 'تأهيل لاختبارها', 'caption_en' => 'Exam prep'],
            ['image' => $this->image('hadaf.png'), 'alt' => 'هدف', 'caption_ar' => 'رسوم مستردة', 'caption_en' => 'Refundable fees'],
        ]], 'json', 0);

        $hero = $this->section($page, 'landing_v2_hero', 1);
        $hero->setField('heading', [
            'ar' => 'خبرتك في المشاريع تستاهل *شهادة PMP* تثبتها',
            'en' => 'Your project experience deserves a *PMP certificate* to prove it',
        ], 'string', 0);
        $hero->setField('subheading', [
            'ar' => 'دورة إدارة المشاريع الاحترافية، تأهيل جاد لاختبار PMP ونعطيك أدوات تطبقها في شغلك من أول أسبوع.',
            'en' => 'Serious PMP exam preparation, plus tools you can apply at work from the very first week.',
        ], 'string', 1);
        $hero->setField('facts', ['value' => [
            ['value_ar' => '35 ساعة', 'value_en' => '35 hours', 'label_ar' => 'تدريب مباشر ومسجل', 'label_en' => 'Live & recorded training'],
            ['value_ar' => '6', 'value_en' => '6', 'label_ar' => 'أيام مسائية', 'label_en' => 'Evenings'],
            ['value_ar' => 'سنتين', 'value_en' => '2 years', 'label_ar' => 'تسجيلات ومحتوى', 'label_en' => 'Recordings & content'],
        ]], 'json', 2);
        $hero->setField('price', ['ar' => '1,199 ريال', 'en' => 'SAR 1,199'], 'string', 3);
        $hero->setField('show_next_batch', ['value' => true], 'boolean', 4);

        $this->seedLeadType($hero);

        $this->seedClientsAndBenefits($page, 2);
    }

    protected function seedLeadType(Section $hero): void
    {
        $typeId = ServiceRequestType::query()->where('slug', 'tlbat')->value('id');

        if ($typeId) {
            $hero->setField('type_id', ['value' => $typeId], 'number', 20);
        }
    }

    /**
     * The clients marquee and benefits sections, shared by the v2 and v3 pages.
     */
    protected function seedClientsAndBenefits(Page $page, int $sortOrder): void
    {
        $this->seedClients($page, $sortOrder);

        $benefits = $this->section($page, 'landing_v2_benefits', $sortOrder + 1);
        $benefits->setField('title', ['ar' => 'كل ما تحتاجه *حتى يوم الاختبار*:', 'en' => 'Everything you need *until exam day*:'], 'string', 0);
        $benefits->setField('during_title', ['ar' => 'أثناء الدورة', 'en' => 'During the course'], 'string', 1);
        $benefits->setField('during_items', ['value' => [
            ['text_ar' => '*المنهج: الإصدار الثامن (2026)*', 'text_en' => '*Curriculum: 8th edition (2026)*', 'note_ar' => 'وفق أحدث منهج من PMI', 'note_en' => 'Aligned with the latest PMI standard'],
            ['text_ar' => 'بث مباشر وتعليم موجّه', 'text_en' => 'Live, instructor-led sessions'],
            ['text_ar' => 'منصة تعلّم خاصة بمران', 'text_en' => 'Miran’s own learning platform'],
            ['text_ar' => 'رسوم مستردة من هدف', 'text_en' => 'Fees refundable through HADAF', 'note_ar' => 'بشروط البرنامج', 'note_en' => 'Program terms apply'],
        ]], 'json', 2);
        $benefits->setField('after_title', ['ar' => 'نبقى معك حتى تجتاز', 'en' => 'With you until you pass'], 'string', 3);
        $benefits->setField('after_items', ['value' => [
            ['text_ar' => 'ورش تقوية دورية مجانية بعد انتهاء الدورة', 'text_en' => 'Free periodic refresher workshops after the course'],
            ['text_ar' => 'أعد الدورة كاملة مجانًا خلال سنة', 'text_en' => 'Retake the full course free within a year'],
            ['text_ar' => 'تسجيلات ومحتوى سنتين', 'text_en' => 'Two years of recordings & content'],
            ['text_ar' => 'بنك أسئلة للاختبار', 'text_en' => 'Exam question bank'],
            ['text_ar' => 'إرشاد لطلب اختبار PMI', 'text_en' => 'Guidance on applying for the PMI exam'],
        ]], 'json', 4);
        $benefits->setField('stats', ['value' => [
            ['value' => '+350', 'label_ar' => 'جهة درّبنا موظفيها', 'label_en' => 'Organizations trained'],
            ['value' => '+20,000', 'label_ar' => 'متدرب منذ 2022', 'label_en' => 'Trainees since 2022'],
        ]], 'json', 5);
    }

    protected function seedClients(Page $page, int $sortOrder): void
    {
        $clients = $this->section($page, 'landing_v2_clients', $sortOrder);
        $clients->setField('logos', ['value' => array_map(
            fn (string $name) => $this->image("{$name}.webp"),
            ['aramco', 'makkah-chamber', 'moi', 'sami', 'takamul', 'momrah', 'mod', 'mutabaqa', 'tga', 'saudi-lime'],
        )], 'json', 0);
    }

    /**
     * Copies a bundled image (public/img/{directory}) to S3 and returns its key;
     * falls back to the public asset path (rendered fine, but not editable) when S3 fails.
     */
    protected function image(string $filename, string $directory = 'landing-v2'): string
    {
        $key = "sections/{$directory}/{$filename}";

        try {
            $disk = Storage::disk('s3');

            if (! $disk->exists($key)) {
                $disk->put($key, file_get_contents(public_path("img/{$directory}/{$filename}")));
            }

            return $key;
        } catch (Throwable $exception) {
            $this->command?->warn("S3 upload failed for [{$filename}], using the public asset: {$exception->getMessage()}");

            return "/img/{$directory}/{$filename}";
        }
    }

    protected function section(Page $page, string $type, int $sortOrder): Section
    {
        return Section::query()->create([
            'page_id' => $page->id,
            'type' => $type,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
