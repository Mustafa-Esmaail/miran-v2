<?php

namespace Tests\Unit;

use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopbarSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_texts_are_read_from_settings(): void
    {
        $settings = app(SettingsService::class);
        $data = $settings->formData();
        $data['topbar_left_text'] = ['ar' => '????', 'en' => 'Left'];
        $data['topbar_center_text'] = ['ar' => '???', 'en' => 'Center'];
        $data['topbar_right_text'] = ['ar' => '????', 'en' => 'Right'];

        $settings->updateFromForm($data);

        $this->assertSame('Left', $settings->getTranslatable('topbar.left_text', 'en'));
        $this->assertSame('Center', $settings->getTranslatable('topbar.center_text', 'en'));
        $this->assertSame('Right', $settings->getTranslatable('topbar.right_text', 'en'));
    }
}
