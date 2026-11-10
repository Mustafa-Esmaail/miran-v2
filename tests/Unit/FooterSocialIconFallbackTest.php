<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterSocialIconFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_renders_with_unknown_social_icon(): void
    {
        $html = \Illuminate\Support\Facades\Blade::render(
            '<x-site.footer :social-links="$socialLinks" :footer-policy-links="[]" />',
            [
                'socialLinks' => [
                    ['label' => 'WhatsApp', 'href' => 'https://example.com', 'icon' => 'whatsapp'],
                ],
            ]
        );

        $this->assertStringContainsString('WhatsApp', $html);
        $this->assertStringContainsString('google.com/maps/embed', $html);
    }
}
