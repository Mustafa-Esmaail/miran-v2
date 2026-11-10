<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDownloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_downloads_page_renders_when_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard.downloads'));

        $response
            ->assertOk()
            ->assertSee(__('web.downloads.empty'));
    }
}
