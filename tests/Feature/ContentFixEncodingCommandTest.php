<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ContentFixEncodingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fixes_mojibake_for_translatable_models(): void
    {
        $mojibake = 'ÃÂÃÂ§ ÃÂªÃÂÃÂ¬ÃÂ¯ ÃÂ¬ÃÂÃÂ³ÃÂ§ÃÂª';
        $expected = mb_convert_encoding($mojibake, 'UTF-8', 'ISO-8859-1');

        $announcement = Announcement::factory()->create([
            'title' => ['ar' => $mojibake],
            'body' => ['ar' => $mojibake],
        ]);

        Artisan::call('content:fix-encoding');

        $announcement->refresh();

        $this->assertSame($expected, $announcement->getTranslation('title', 'ar'));
        $this->assertSame($expected, $announcement->getTranslation('body', 'ar'));
    }
}
