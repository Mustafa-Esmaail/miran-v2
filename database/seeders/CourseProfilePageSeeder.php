<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Page;
use Illuminate\Database\Seeder;

class CourseProfilePageSeeder extends Seeder
{
    /**
     * Old themiran.net slug => matched course slug.
     *
     * @var array<string, string>
     */
    private const array SLUG_TO_COURSE_SLUG = [
        'Pr-aPH-off' => 'aphri',
        'Pr-aPH-on' => 'aphri',
        'Pr-Ex-off' => 'excel',
        'Pr-PMP-off' => 'pmp-offline',
        'Pr-PBI-off' => 'power-bi-offline',
        'Pr-AI-on' => 'ai-skills-online',
        'Pr-CAP-on' => 'capm',
        'Pr-CCM-on' => 'ccmp-online',
        'Pr-CS-on' => 'cs-online',
        'Pr-pkg-Ex' => 'data-package',
        'Pr-pkg-AI' => 'tech-package',
        'Pr-EX-on' => 'advanced-excel',
        'Pr-MP-on' => 'management-package',
        'Pr-GRC-on' => 'grcp',
        'Pr-HR-on' => 'hr-online',
        'Pr-ISO-on' => 'iso-9001',
        'Pr-KPI-on' => 'kpi',
        'Pr-Mic-on' => 'microsoft-office-skills-online',
        'Pr-MS-on' => 'microsoft-project',
        'Pr-OSH-on' => 'osha',
        'Pr-pkg-PMP' => 'pbmp',
        'Pr-PMP-on' => 'pmp-online',
        'Pr-PBI-on' => 'power-bi',
        'Pr-Pyth-0n' => 'python-online',
        'Pr-RMP-on' => 'rmp',
        'Pr-TQM-on' => 'tqm-online',
        'Pr-PBA-on' => 'business-analyst-pba',
        'Pr-NEBOSH-on' => 'safety-course',
        'Pr-CLTD-on' => 'cltd',
        'Pr-ESL-on' => 'executive-certificate-in-strategy-leadership',
        'Pr-TTT-on' => 'train-the-trainer-ttt',
        'Pr-CFA-on' => 'cfa-%d9%85%d8%ad%d9%84%d9%84-%d9%85%d8%a7%d9%84%d9%8a-%d9%85%d8%b9%d8%aa%d9%85%d8%af',
        'Pr-SHRM-on' => 'shrm',
        'Pr-DMI-on' => 'dmi',
        'Pr-CCNA-on' => 'ccna',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::SLUG_TO_COURSE_SLUG as $slug => $courseSlug) {
            $course = Course::where('slug', $courseSlug)->first();

            if (! $course) {
                $this->command?->warn("Skipping {$slug}: course '{$courseSlug}' not found.");

                continue;
            }

            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $course->getTranslations('title'),
                    'template' => 'course-profile',
                    'landable_type' => Course::class,
                    'landable_id' => $course->id,
                    'is_active' => true,
                    'is_published' => true,
                ]
            );
        }
    }
}
