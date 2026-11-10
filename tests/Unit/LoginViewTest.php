<?php

namespace Tests\Unit;

use Tests\TestCase;

class LoginViewTest extends TestCase
{
    public function test_login_view_has_show_password_toggle(): void
    {
        $contents = file_get_contents(resource_path('views/auth/login.blade.php'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('x-data="{ showPassword: false }"', $contents);
        $this->assertStringContainsString("x-bind:type=\"showPassword ? 'text' : 'password'\"", $contents);
        $this->assertStringContainsString("__('auth.show_password')", $contents);
        $this->assertStringContainsString("__('auth.hide_password')", $contents);
    }

    public function test_auth_translations_define_show_and_hide_password_labels(): void
    {
        $englishTranslations = require lang_path('en/auth.php');
        $arabicTranslations = require lang_path('ar/auth.php');

        $this->assertSame('Show', $englishTranslations['show_password']);
        $this->assertSame('Hide', $englishTranslations['hide_password']);
        $this->assertArrayHasKey('show_password', $arabicTranslations);
        $this->assertArrayHasKey('hide_password', $arabicTranslations);
    }
}
