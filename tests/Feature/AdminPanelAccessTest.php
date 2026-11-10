<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function active_user_can_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $response = $this->actingAs($user, 'admin')->get('/admin/dashboard');

        $response->assertOk();
    }

    #[Test]
    public function deleted_status_user_is_denied_admin_panel_access(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_DELETED]);

        $response = $this->actingAs($user, 'admin')->get('/admin/dashboard');

        $response->assertForbidden();
    }

    #[Test]
    public function suspended_status_user_is_denied_admin_panel_access(): void
    {
        $user = User::factory()->create(['status' => User::STATUS_SUSPENDED]);

        $response = $this->actingAs($user, 'admin')->get('/admin/dashboard');

        $response->assertForbidden();
    }
}
