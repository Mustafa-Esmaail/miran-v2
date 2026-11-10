<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminOpsDashboardPageTest extends TestCase
{
    #[Test]
    public function admin_dashboard_route_is_available_and_protected_by_authentication(): void
    {
        User::factory()->create();

        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }
}
