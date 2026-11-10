<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\ServiceRequests\Schemas\ServiceRequestForm;
use App\Models\ServiceRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class HasStatusDateFieldTest extends TestCase
{
    use RefreshDatabase;

    private function invokePrivateStatic(string $method, array $args = []): mixed
    {
        $reflection = new ReflectionMethod(ServiceRequestForm::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(null, ...$args);
    }

    public function test_status_requires_date_reflects_the_status_flag(): void
    {
        ServiceRequestStatus::query()->updateOrCreate(['slug' => 'unit_test_dated_status'], [
            'name' => ['en' => 'Dated status'],
            'requires_date' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ServiceRequestStatus::query()->updateOrCreate(['slug' => 'unit_test_plain_status'], [
            'name' => ['en' => 'Plain status'],
            'requires_date' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->assertTrue($this->invokePrivateStatic('statusRequiresDate', ['unit_test_dated_status']));
        $this->assertFalse($this->invokePrivateStatic('statusRequiresDate', ['unit_test_plain_status']));
        $this->assertFalse($this->invokePrivateStatic('statusRequiresDate', [null]));
    }
}
