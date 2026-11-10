<?php

namespace Database\Seeders;

use App\Models\Page;

/**
 * Creates the PMP online landing page with the v3 hero (landingv3.html,
 * 2026-10-27). It shares the landing-v2 template, clients and benefits
 * sections with LandingV2PageSeeder; only the hero differs.
 *
 * php artisan db:seed --class=LandingV3PageSeeder
 */
class LandingV3PageSeeder extends LandingV2PageSeeder
{
    protected const PAGE_SLUG = 'pmp-online-v3';

    protected function seedSections(Page $page): void
    {
        $hero = $this->section($page, 'landing_v3_hero', 0);
        $hero->setField('heading', [
            'ar' => 'تبي تاخذ *شهادة PMP* وتعدّي الاختبار من أول مرة؟',
            'en' => 'Want your *PMP certificate* and to pass the exam on the first try?',
        ], 'string', 0);
        $hero->setField('subheading', [
            'ar' => 'تدريب مسائي مكثف + محاكاة الاختبار الفعلي، ونتابع معك لين تنجح.',
            'en' => 'Intensive evening training + real exam simulations, and we follow up with you until you pass.',
        ], 'string', 1);
        $hero->setField('logo', ['value' => $this->image('miran-light.svg')], 'string', 2);
        $hero->setField('facts', ['value' => [
            ['value_ar' => '35 ساعة', 'value_en' => '35 hours', 'label_ar' => 'تدريب مباشر ومسجل', 'label_en' => 'Live & recorded training'],
            ['value_ar' => '6', 'value_en' => '6', 'label_ar' => 'أيام مسائية', 'label_en' => 'Evenings'],
            ['value_ar' => 'PMI', 'value_en' => 'PMI', 'label_ar' => 'تؤهلك للاختبار الدولي', 'label_en' => 'Prepares you for the PMI exam', 'image' => $this->image('pmi-light.png')],
        ]], 'json', 3);
        $hero->setField('badges', ['value' => [
            ['image' => $this->image('tvtc.png'), 'name_ar' => 'المؤسسة العامة للتدريب التقني والمهني', 'name_en' => 'Technical and Vocational Training Corporation', 'detail_ar' => 'ترخيص 2241119295411812', 'detail_en' => 'Licence 2241119295411812'],
            ['image' => $this->image('nelc.png'), 'name_ar' => 'المركز الوطني للتعليم الإلكتروني', 'name_en' => 'National eLearning Center', 'detail_ar' => 'ترخيص 2000298631040991', 'detail_en' => 'Licence 2000298631040991'],
        ]], 'json', 4);
        $hero->setField('price', ['ar' => '1,199', 'en' => '1,199'], 'string', 5);
        $hero->setField('show_currency_icon', ['value' => true], 'boolean', 6);
        $hero->setField('price_note', ['ar' => 'قابلة للاسترداد من', 'en' => 'Refundable through'], 'string', 7);
        $hero->setField('price_note_image', ['value' => $this->image('hadaf.png')], 'string', 8);
        $hero->setField('show_next_batch', ['value' => true], 'boolean', 9);

        $this->seedLeadType($hero);

        $this->seedClientsAndBenefits($page, 1);
    }
}
