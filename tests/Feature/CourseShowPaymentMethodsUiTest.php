<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseShowPaymentMethodsUiTest extends TestCase
{
    public function test_course_show_contains_payment_methods_section(): void
    {
        $contents = file_get_contents(base_path('resources/views/livewire/courses/course-show.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("__('web.course.payment_methods')", $contents);
        $this->assertStringContainsString('@foreach ($paymentMethods as $method)', $contents);
        $this->assertStringContainsString("\$method['logo']", $contents);
        $this->assertStringContainsString("\$method['label']", $contents);
    }
}
