<?php

namespace Database\Seeders;

use App\Models\Page;

/**
 * Shared builder of the course landing pages (Excel, KPI, Power BI): hero
 * with the lead form, the clients marquee, then facts and benefits. Each
 * course seeder only supplies its page attributes, content and image folder
 * (public/img/landing-{course}).
 */
abstract class LandingCoursePageSeeder extends LandingV2PageSeeder
{
    protected const IMAGE_DIRECTORY = '';

    /**
     * @return array{
     *     heading: array<string, string>,
     *     subheading: array<string, string>,
     *     price: string,
     *     old_price?: string,
     *     installment: array<string, string>,
     *     facts: list<array{value_ar: string, value_en: string, label_ar: string, label_en: string}>,
     *     tools_title: array<string, string>,
     *     tools: list<array{ar: string, en: string}>,
     *     extras: list<array{ar: string, en: string}>,
     *     stories: list<array{title_ar: string, title_en: string, summary_ar: string, summary_en: string, now_ar: string, now_en: string, after_ar: string, after_en: string}>,
     * }
     */
    abstract protected function content(): array;

    protected function seedSections(Page $page): void
    {
        $content = $this->content();

        $hero = $this->section($page, 'landing_course_hero', 0);
        $hero->setField('badges', ['value' => [
            ['image' => $this->courseImage('license-tvtc.webp'), 'name_ar' => 'المؤسسة العامة للتدريب التقني والمهني', 'name_en' => 'Technical and Vocational Training Corporation', 'detail_ar' => 'ترخيص 2241119295411812', 'detail_en' => 'Licence 2241119295411812'],
            ['image' => $this->courseImage('license-nelc.webp'), 'name_ar' => 'المركز الوطني للتعليم الإلكتروني', 'name_en' => 'National eLearning Center', 'detail_ar' => 'ترخيص 2000298631040991', 'detail_en' => 'Licence 2000298631040991'],
        ]], 'json', 0);
        $hero->setField('heading', $content['heading'], 'string', 1);
        $hero->setField('subheading', $content['subheading'], 'string', 2);
        $hero->setField('logo', ['value' => $this->image('miran-light.svg')], 'string', 3);
        $hero->setField('price', ['ar' => $content['price'], 'en' => $content['price']], 'string', 4);
        if (filled($content['old_price'] ?? null)) {
            $hero->setField('old_price', ['ar' => $content['old_price'], 'en' => $content['old_price']], 'string', 5);
        }
        $hero->setField('show_currency_icon', ['value' => true], 'boolean', 6);
        $hero->setField('installment', $content['installment'], 'string', 7);
        $hero->setField('installment_badge', ['ar' => 'تمارا', 'en' => 'Tamara'], 'string', 8);
        $hero->setField('show_next_batch', ['value' => true], 'boolean', 9);

        $this->seedLeadType($hero);

        $this->seedClients($page, 1);

        $benefits = $this->section($page, 'landing_course_benefits', 2);
        $benefits->setField('facts', ['value' => $content['facts']], 'json', 0);
        $benefits->setField('title', $content['tools_title'], 'string', 1);
        $benefits->setField('tools', ['value' => $this->iconItems($content['tools'], 1)], 'json', 2);
        $benefits->setField('extras_title', ['ar' => 'ومعها أيضًا', 'en' => 'Also included'], 'string', 3);
        $benefits->setField('extras', ['value' => $this->iconItems($content['extras'], 6)], 'json', 4);
        $benefits->setField('stories_title', ['ar' => 'ماذا يتغيّر في عملك بعد الدورة؟', 'en' => 'What changes at work after the course?'], 'string', 5);
        $benefits->setField('stories_subtitle', ['ar' => 'ست قدرات، كل واحدة بمثال من بيئة العمل', 'en' => 'Six capabilities, each with an example from the workplace'], 'string', 6);
        $benefits->setField('stories', ['value' => $content['stories']], 'json', 7);
        $benefits->setField('stats', ['value' => [
            ['value' => '+350', 'label_ar' => 'جهة درّبنا موظفيها', 'label_en' => 'Organizations trained'],
            ['value' => '+20,000', 'label_ar' => 'متدرب منذ 2022', 'label_en' => 'Trainees since 2022'],
        ]], 'json', 8);
    }

    /**
     * Pairs each item with its numbered icon (icon-01.webp, icon-02.webp, ...).
     *
     * @param  list<array{ar: string, en: string}>  $items
     * @return list<array{image: string, text_ar: string, text_en: string}>
     */
    protected function iconItems(array $items, int $firstIcon): array
    {
        return array_map(fn (array $item, int $index) => [
            'image' => $this->courseImage(sprintf('icon-%02d.webp', $firstIcon + $index)),
            'text_ar' => $item['ar'],
            'text_en' => $item['en'],
        ], $items, array_keys($items));
    }

    protected function courseImage(string $filename): string
    {
        return $this->image($filename, static::IMAGE_DIRECTORY);
    }
}
