<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Page;
use App\Models\Section;
use App\Models\ServiceRequestType;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    /**
     * Each entry: [landing_slug, course_slug, is_active]
     * course_slug = null means no course attached (standalone landing page).
     */
    private array $pages = [
        ['ai-2025',                  'ai-skills-online',               true],
        ['rmp-on-2025',              'rmp',                            true],
        ['aphri-2025',               'aphri',                          true],
        ['aphri-off-2025',           'aphri-off',                      true],
        ['microsoft-office-on-2025', 'microsoft-office-skills-online', true],
        ['capm-2025',                'capm',                           true],
        ['pmp-on-2025',              'pmp-online',                     true],
        ['pmp-off-riyadh-2025',      'pmp-offline',                    true],
        ['iso-2025',                 'iso9001',                        true],
        ['power-bi-on-2025',         'power-bi',                       true],
        ['power-bi-off-2025',        'power-bi-offline',               true],
        ['grcp-on-2025',             'grcp',                           true],
        ['tqm-on-2025',              'tqm-online',                     true],
        ['kpi-on-2025',              'kpis-online',                    true],
        ['osha-on-2025',             'osha',                           true],
        ['excel-on-2025',            'advanced-excel',                 true],
        ['excel-off-2025',           'excel',                          true],
        ['ccmp-on-2025',             'ccmp-online',                    true],
        ['ms-pro-on-2025',           'ms-project',                     true],
        ['python-on-2025',           'python-online',                  true],
        ['cs-on-2025',               'cs-online',                      true],
        ['hr-on-2025',               'hr-online',                      true],
        ['hr-off-2025',              'hr-physical',                    false],
        ['fmp-on-2025',              'management-package',             true],
        ['photograpy-on-2025',       'photography',                    true],
        ['makeup-off-2025',          'makeup',                         true],
        ['pbmp-on-2025',             'pbmp',                           true],
        ['ptech-on-2025',            'tech-package',                   true],
        ['pdata-on-2025',            'data-package',                   true],
        ['contact-2025',             null,                             true],
    ];

    /** Scraped hero slider images per landing page slug. */
    private array $slideImages = [
        'ai-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2026/06/compressed-AI-SKILLS-COURSE5-1.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2.png',
        ],
        'rmp-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-RMP-ONLINE.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'aphri-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-APHRI-Online.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'microsoft-office-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-Microsoft-office-online.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'capm-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-CAPM.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'pmp-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-PMP-online.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'pmp-off-riyadh-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-PMP-offline.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'iso-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-ISO-9001.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'power-bi-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-POWER-BI-ONLINE-1536x864.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'power-bi-off-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-POWER-BI-OFFLINE.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'grcp-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-GRCP.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'tqm-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-TQM.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'kpi-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-KPI.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'osha-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-OSHA.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'excel-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/11/converted-Microsoft-excel-online.webp',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'excel-off-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'ccmp-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'ms-pro-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1.png',
        ],
        'python-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'cs-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'hr-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'fmp-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'photograpy-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'makeup-off-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'pbmp-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'ptech-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
        'pdata-on-2025' => [
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-2-1.png',
            'https://backup.themiran.net/wp-content/uploads/2025/06/banner-1-1.png',
        ],
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public function slideImages(): array
    {
        return $this->slideImages;
    }

    public function run(): void
    {
        $courseClass = Course::class;
        $leadFormTypeId = ServiceRequestType::query()->where('slug', 'tlbat')->value('id');

        foreach ($this->pages as [$slug, $courseSlug, $isActive]) {
            $course = $courseSlug
                ? Course::query()->where('slug', $courseSlug)->first()
                : null;

            if ($courseSlug && ! $course) {
                $this->command->warn("Course not found for slug [{$courseSlug}] — skipping [{$slug}].");

                continue;
            }

            $title = $course
                ? $course->getTranslations('title')
                : ['ar' => $slug, 'en' => $slug];

            $page = Page::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'template' => 'landing-page',
                    'landable_type' => $course ? $courseClass : null,
                    'landable_id' => $course?->id,
                    'is_published' => true,
                    'is_active' => $isActive,
                ]
            );

            $this->seedSections($page, $leadFormTypeId);

            $this->command->info("Created/updated landing page [{$slug}]".($course ? " → course [{$courseSlug}]" : '').'.');
        }
    }

    private function seedSections(Page $page, ?int $leadFormTypeId = null): void
    {
        if ($page->sections()->exists()) {
            return;
        }

        $hero = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'landing_hero',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $images = $this->slideImages[$page->slug] ?? [];

        if (! empty($images)) {
            $slides = array_map(fn (string $url) => ['background_image' => $url], $images);
            $hero->setField('slides', ['value' => $slides], 'json', 0);
        }

        $leadForm = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'landing_lead_form',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        if ($leadFormTypeId) {
            $leadForm->setField('type_id', ['value' => $leadFormTypeId], 'number');
        }
    }
}
