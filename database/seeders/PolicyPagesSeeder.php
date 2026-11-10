<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Section;
use App\Services\SectionFieldNormalizer;
use Illuminate\Database\Seeder;

class PolicyPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => ['en' => 'Pricing Policy', 'ar' => 'Pricing Policy'],
                'slug' => 'pricing-policy',
                'body' => ['en' => '', 'ar' => ''],
                'sections' => [
                    [
                        'type' => 'policy_text',
                        'title' => 'Pricing Policy',
                        'subtitle' => 'Understand our pricing rules',
                        'content' => [
                            'body_en' => '<p>All prices are shown in SAR and may change without notice.</p>',
                            'body_ar' => '<p>All prices are shown in SAR and may change without notice.</p>',
                        ],
                        'sort_order' => 10,
                    ],
                ],
            ],
        ];

        foreach ($pages as $pageData) {
            $page = Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                [
                    'title' => $pageData['title'],
                    'body' => $pageData['body'],
                    'template' => 'default',
                    'is_published' => true,
                    'is_active' => true,
                ]
            );

            foreach ($pageData['sections'] as $sectionData) {
                $existing = $page->sections()
                    ->where('type', $sectionData['type'])
                    ->where('title', $sectionData['title'])
                    ->first();

                if ($existing) {
                    continue;
                }

                $section = Section::create([
                    'page_id' => $page->id,
                    'type' => $sectionData['type'],
                    'title' => $sectionData['title'],
                    'subtitle' => $sectionData['subtitle'],
                    'is_active' => true,
                    'sort_order' => $sectionData['sort_order'],
                ]);

                $this->syncSectionFields($section, $sectionData['content']);
            }
        }
    }

    private function syncSectionFields(Section $section, array $content): void
    {
        $fields = SectionFieldNormalizer::extractFields($content);

        foreach ($fields as $key => $payload) {
            $section->setField($key, $payload['value'], $payload['type']);
        }
    }
}
