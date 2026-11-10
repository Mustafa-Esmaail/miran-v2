<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandingV3HeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_v3_hero_renders_logo_fact_badges_price_and_lead_form(): void
    {
        $page = Page::query()->create([
            'title' => ['en' => 'Landing v3', 'ar' => 'Landing v3'],
            'slug' => 'landing-v3-'.Str::lower(Str::random(8)),
            'body' => ['en' => '', 'ar' => ''],
            'template' => 'landing-v2',
            'is_published' => true,
            'is_active' => true,
        ]);

        $hero = Section::query()->create([
            'page_id' => $page->id,
            'type' => 'landing_v3_hero',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $hero->setField('heading', ['ar' => 'تبي تاخذ *PMP*؟', 'en' => 'Want your *PMP*?'], 'string');
        $hero->setField('facts', ['value' => [
            ['value_ar' => '35 ساعة', 'value_en' => '35 hours', 'label_ar' => 'تدريب', 'label_en' => 'Training'],
            ['value_ar' => 'PMI', 'label_ar' => 'تؤهلك للاختبار', 'image' => '/img/landing-v2/pmi-light.png'],
        ]], 'json');
        $hero->setField('badges', ['value' => [
            ['name_ar' => 'المؤسسة العامة للتدريب التقني والمهني', 'detail_ar' => 'ترخيص 2241119295411812'],
        ]], 'json');
        $hero->setField('price', ['ar' => '1,199', 'en' => '1,199'], 'string');

        $this->withoutVite();

        $this->get('/'.$page->slug)
            ->assertOk()
            ->assertSee('<em>PMP</em>', false)
            ->assertSee('img/landing-v2/miran-light.svg', false)
            ->assertSee('img/landing-v2/pmi-light.png', false)
            ->assertSee('ترخيص 2241119295411812')
            ->assertSee('class="sar"', false)
            ->assertSee('35 ساعة')
            ->assertSee('lv2-phone', false)
            ->assertSee('class="mark" src="', false)
            ->assertSee('width="360" height="388" fetchpriority="high"', false)
            ->assertSee('width="33" height="44"', false)
            ->assertSee('fonts/cairo/cairo-arabic.woff2', false);
    }
}
