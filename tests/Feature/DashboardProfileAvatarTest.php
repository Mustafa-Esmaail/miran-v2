<?php

namespace Tests\Feature;

use App\Livewire\DashboardProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_upload_falls_back_to_public_when_s3_bucket_missing(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.bucket' => null,
        ]);

        Storage::fake('public');

        $user = User::factory()->create([
            'avatar_path' => 'avatars/old.png',
            'birth_date' => now()->subYears(25)->toDateString(),
            'gender' => 'male',
        ]);

        Storage::disk('public')->put('avatars/old.png', 'old');

        Livewire::actingAs($user)
            ->test(DashboardProfile::class)
            ->set('avatar', UploadedFile::fake()->image('avatar.png'))
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        Storage::disk('public')->assertMissing('avatars/old.png');
    }
}
