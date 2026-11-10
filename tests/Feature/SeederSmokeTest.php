<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\QuestionAnswer;
use App\Models\QuestionVote;
use App\Models\User;
use Database\Seeders\AnnouncementSeeder;
use Database\Seeders\CommentReplySeeder;
use Database\Seeders\CommentSeeder;
use Database\Seeders\CourseQuestionSeeder;
use Database\Seeders\QuestionAnswerSeeder;
use Database\Seeders\QuestionVoteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_run_without_faker_dependency(): void
    {
        $user = User::query()->create([
            'name' => 'Seeder User',
            'email' => 'seeder@example.com',
            'password' => 'password',
        ]);

        $category = Category::query()->create([
            'name' => ['en' => 'Seeder Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Seeder Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'draft',
        ]);

        $question = CourseQuestion::query()->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => 'Seeder question',
            'content' => 'Seeder question content.',
        ]);

        QuestionAnswer::query()->create([
            'question_id' => $question->id,
            'user_id' => $user->id,
            'content' => 'Seeder answer content.',
            'status' => 'approved',
            'is_accepted' => false,
        ]);

        Comment::query()->create([
            'commentable_id' => $course->id,
            'commentable_type' => Course::class,
            'user_id' => $user->id,
            'content' => 'Seeder comment.',
            'approved' => true,
            'hidden' => false,
        ]);

        $this->seed(AnnouncementSeeder::class);
        $this->seed(CourseQuestionSeeder::class);
        $this->seed(QuestionAnswerSeeder::class);
        $this->seed(QuestionVoteSeeder::class);
        $this->seed(CommentSeeder::class);
        $this->seed(CommentReplySeeder::class);

        $this->assertGreaterThan(0, Announcement::query()->count());
        $this->assertGreaterThan(0, CourseQuestion::query()->count());
        $this->assertGreaterThan(0, QuestionAnswer::query()->count());
        $this->assertGreaterThan(0, QuestionVote::query()->count());
        $this->assertGreaterThan(0, Comment::query()->count());
        $this->assertGreaterThan(0, CommentReply::query()->count());
    }
}
