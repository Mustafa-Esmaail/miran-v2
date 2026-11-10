<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseShowPreviewSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->where('slug', 'strategic-leadership')->first()
            ?? Course::query()->first();

        if (! $course) {
            $this->command->warn('No courses found. Run CourseCatalogSeeder first.');

            return;
        }

        $course->update([
            'timeline_highlights' => [
                [
                    'text_ar' => "12 ساعة تدريبية\nمحتوى شامل ومتكامل",
                    'text_en' => "12 Training Hours\nComprehensive Content",
                    'icon' => null,
                ],
                [
                    'text_ar' => "4 أيام تدريب\nجلسات مكثفة",
                    'text_en' => "4 Training Days\nIntensive Sessions",
                    'icon' => null,
                ],
                [
                    'text_ar' => "شهادة معتمدة\nمعترف بها دولياً",
                    'text_en' => "Accredited Certificate\nInternationally Recognized",
                    'icon' => null,
                ],
                [
                    'text_ar' => "تدريب هجين\nحضوري وعن بُعد",
                    'text_en' => "Hybrid Training\nIn-Person & Remote",
                    'icon' => null,
                ],
            ],
            'timeline_items' => [
                [
                    'title_ar' => 'التعريف بالدورة',
                    'title_en' => 'Course Introduction',
                    'description_ar' => '<p>تُقدّم هذه الدورة التدريبية منهجاً متكاملاً في القيادة الاستراتيجية، مصمماً خصيصاً لتطوير مهارات القادة وصنّاع القرار في المؤسسات الحديثة. ستتعلم كيفية صياغة الرؤية وبناء استراتيجيات فعّالة تُحقق النتائج المرجوة.</p>',
                    'description_en' => '<p>This training program offers a comprehensive approach to strategic leadership, specifically designed to develop the skills of leaders and decision-makers in modern organizations. You will learn how to craft vision and build effective strategies that deliver results.</p>',
                    'image' => null,
                ],
                [
                    'title_ar' => 'مهارات التفكير الاستراتيجي',
                    'title_en' => 'Strategic Thinking Skills',
                    'description_ar' => '<p>يتناول هذا المحور تطوير قدرات التحليل والتخطيط الاستراتيجي، بما يشمل:</p><ul><li>تحليل البيئة الداخلية والخارجية (SWOT)</li><li>بناء الخرائط الاستراتيجية</li><li>تحديد الأولويات وإدارة الموارد</li></ul>',
                    'description_en' => '<p>This module covers developing strategic analysis and planning capabilities, including:</p><ul><li>Internal and external environment analysis (SWOT)</li><li>Building strategic maps</li><li>Setting priorities and resource management</li></ul>',
                    'image' => null,
                ],
                [
                    'title_ar' => 'قيادة الفرق عالية الأداء',
                    'title_en' => 'Leading High-Performance Teams',
                    'description_ar' => '<p>تتناول هذه الوحدة أساليب بناء الفرق وتحفيزها لتحقيق أعلى مستويات الأداء، مع التركيز على التواصل الفعّال وإدارة التوقعات والتعامل مع التحديات.</p>',
                    'description_en' => '<p>This unit covers team building and motivation techniques to achieve peak performance levels, focusing on effective communication, expectation management, and handling challenges.</p>',
                    'image' => null,
                ],
                [
                    'title_ar' => 'اتخاذ القرار في بيئات غير مؤكدة',
                    'title_en' => 'Decision-Making Under Uncertainty',
                    'description_ar' => '<p>يستعرض هذا الجزء أدوات وأُطر اتخاذ القرار في ظل الغموض وضغط الوقت، مع تطبيقات عملية على حالات دراسية حقيقية من بيئات الأعمال العربية والعالمية.</p>',
                    'description_en' => '<p>This section reviews decision-making tools and frameworks under ambiguity and time pressure, with practical applications on real case studies from Arab and global business environments.</p>',
                    'image' => null,
                ],
                [
                    'title_ar' => 'قيادة التحول والتغيير المؤسسي',
                    'title_en' => 'Leading Organizational Transformation',
                    'description_ar' => '<p>يُعرّف هذا المحور المشاركين بمنهجيات إدارة التغيير ومراحله، وكيفية التعامل مع مقاومة التغيير وبناء ثقافة مؤسسية تدعم التطور المستمر.</p>',
                    'description_en' => '<p>This module introduces participants to change management methodologies and phases, and how to handle resistance to change while building an organizational culture that supports continuous development.</p>',
                    'image' => null,
                ],
                [
                    'title_ar' => 'تطبيقات ومشروع ختامي',
                    'title_en' => 'Applications & Final Project',
                    'description_ar' => '<p>يختتم البرنامج بمشروع تطبيقي شامل يُقدّمه المشاركون أمام لجنة تقييم متخصصة، حيث يُترجمون ما تعلموه إلى خطة استراتيجية قابلة للتنفيذ في مؤسساتهم.</p>',
                    'description_en' => '<p>The program concludes with a comprehensive applied project presented before a specialized evaluation panel, where participants translate their learning into an actionable strategic plan for their organizations.</p>',
                    'image' => null,
                ],
            ],
        ]);

        $this->command->info("Course \"{$course->getTranslation('title', 'en')}\" (slug: {$course->slug}) updated with timeline data.");
        $this->command->info('Visit: /courses/'.$course->slug);
    }
}
