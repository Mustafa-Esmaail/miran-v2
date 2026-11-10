<?php

namespace Tests\Unit;

use App\Filament\Auth\Register;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\AuthUiEnhancerRegister;
use PHPUnit\Framework\TestCase;

class FilamentAuthRegisterTest extends TestCase
{
    public function test_register_page_extends_auth_ui_enhancer_register(): void
    {
        $this->assertTrue(is_subclass_of(Register::class, AuthUiEnhancerRegister::class));
    }
}
