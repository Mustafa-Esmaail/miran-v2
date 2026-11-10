<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserAvatarUrlTest extends TestCase
{
    public function test_avatar_url_returns_null_when_empty(): void
    {
        $user = new User;

        $this->assertNull($user->avatar_url);
    }

    public function test_avatar_url_returns_http_path(): void
    {
        $user = new User;
        $user->avatar_path = 'https://example.com/avatar.png';

        $this->assertSame('https://example.com/avatar.png', $user->avatar_url);
    }
}
