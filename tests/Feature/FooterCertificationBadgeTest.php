<?php

namespace Tests\Feature;

use Tests\TestCase;

class FooterCertificationBadgeTest extends TestCase
{
    public function test_footer_contains_nelc_entity_verification_badge_iframe(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/site/footer.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('https://services.futurex.sa/entity-badge?code=aDGwDqumV9yqqqd8', $contents);
        $this->assertStringContainsString('<iframe', $contents);
        $this->assertStringContainsString('title="NELC Verified Entity"', $contents);
        $this->assertStringContainsString('referrerpolicy="unsafe-url"', $contents);
    }
}
