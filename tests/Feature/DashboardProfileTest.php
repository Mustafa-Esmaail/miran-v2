<?php

namespace Tests\Feature;

use App\Livewire\DashboardProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_identity_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DashboardProfile::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('phone', '0550000000')
            ->set('national_id', '1234567890')
            ->set('nationality', 'Saudi')
            ->set('birth_date', '1990-01-01')
            ->set('gender', 'male')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'national_id' => '1234567890',
            'nationality' => 'Saudi',
        ]);
    }

    public function test_it_shows_avatar_path_after_upload(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        Livewire::actingAs($user)
            ->test(DashboardProfile::class)
            ->set('avatar', $file)
            ->set('birth_date', '1990-01-01')
            ->set('gender', 'male')
            ->call('save')
            ->assertSet('avatar_path', $user->fresh()->avatar_path);

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('s3')->assertExists($user->avatar_path);
    }
}
