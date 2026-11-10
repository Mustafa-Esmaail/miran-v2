<?php

namespace Tests\Unit;

use App\Services\MiranCourseImporter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MiranCourseImporterTimelineExtractionTest extends TestCase
{
    #[Test]
    public function it_extracts_expected_timeline_items_from_arabic_course_sections(): void
    {
        $importer = app(MiranCourseImporter::class);

        $text = implode("\n", [
            'التعريف بالدورة',
            'سطر 1',
            'سطر 2',
            'سطر 3',
            'الأهداف التفصيلية للدورة',
            '- هدف 1',
            '- هدف 2',
            'الفئة المستهدفة',
            'المديرون',
            'مميزات الدورة',
            'ميزة قوية',
            'في نهاية الدورة سيكون المشاركون قادرون على :',
            'تطبيق المنهج',
        ]);

        $method = new \ReflectionMethod($importer, 'extractTimelineItemsFromDescription');
        $method->setAccessible(true);
        $items = $method->invoke($importer, $text);

        $this->assertCount(5, $items);
        $this->assertSame('التعريف بالدورة', $items[0]['title_ar']);
        $this->assertSame('سطر 1'."\n".'سطر 2'."\n".'سطر 3', $items[0]['description_ar']);
        $this->assertSame('الأهداف التفصيلية للدورة', $items[1]['title_ar']);
    }

    #[Test]
    public function it_builds_main_description_as_three_lines_from_about_section(): void
    {
        $importer = app(MiranCourseImporter::class);

        $items = [
            [
                'title_ar' => 'التعريف بالدورة',
                'title_en' => 'التعريف بالدورة',
                'description_ar' => "سطر 1\nسطر 2\nسطر 3\nسطر 4",
                'description_en' => "سطر 1\nسطر 2\nسطر 3\nسطر 4",
                'image' => null,
            ],
        ];

        $method = new \ReflectionMethod($importer, 'extractAboutSummaryFromTimelineItems');
        $method->setAccessible(true);
        $summary = $method->invoke($importer, $items);

        $this->assertSame("سطر 1\nسطر 2\nسطر 3", $summary);
    }

    #[Test]
    public function it_extracts_timeline_items_from_h3_html_and_merges_duplicate_headings(): void
    {
        $importer = app(MiranCourseImporter::class);

        $html = <<<'HTML'
<h3>التعريف بالدورة</h3>
<p>نص التعريف 1</p>
<h3>في نهاية الدورة سيكون المشاركون قادرون على :</h3>
<p>نتيجة 1</p>
<h3>مميزات الدورة</h3>
<p>ميزة 1</p>
<h3>مميزات الدورة</h3>
<p>ميزة 2</p>
HTML;

        $method = new \ReflectionMethod($importer, 'extractTimelineItemsFromDescriptionHtml');
        $method->setAccessible(true);
        $items = $method->invoke($importer, $html);

        $this->assertCount(3, $items);
        $this->assertSame('التعريف بالدورة', $items[0]['title_ar']);
        $this->assertSame('في نهاية الدورة سيكون المشاركون قادرون على :', $items[1]['title_ar']);
        $this->assertSame('مميزات الدورة', $items[2]['title_ar']);
        $this->assertSame("ميزة 1\nميزة 2", $items[2]['description_ar']);
    }
}
