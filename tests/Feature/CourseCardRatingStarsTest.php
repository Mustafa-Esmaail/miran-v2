<?php

namespace Tests\Feature;

use Tests\TestCase;

class CourseCardRatingStarsTest extends TestCase
{
    public function test_course_card_shows_five_star_rating_row_next_to_price(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/course-card.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('@foreach (range(1, 5) as $star)', $contents);
        $this->assertStringContainsString("\$card['ratingAverage']", $contents);
        $this->assertStringContainsString("\$card['ratingValue']", $contents);
    }
}
