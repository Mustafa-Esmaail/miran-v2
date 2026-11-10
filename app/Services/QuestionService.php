<?php

namespace App\Services;

use App\Enums\QuestionAnswerStatus;
use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\User;
use App\Repositories\Contracts\CourseQuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QuestionService
{
    public function __construct(
        private readonly CourseQuestionRepositoryInterface $questions,
        private readonly NotificationService $notifications
    ) {}

    public function createQuestion(
        Course $course,
        ?User $user,
        string $title,
        string $content,
        ?string $guestName = null,
        ?string $guestEmail = null
    ): CourseQuestion {
        $author = $user ?? $this->getGuestUser();

        $question = $this->questions->create([
            'course_id' => $course->id,
            'user_id' => $author->id,
            'guest_name' => $user ? null : $guestName,
            'guest_email' => $user ? null : $guestEmail,
            'title' => $title,
            'content' => $content,
        ]);

        $this->notifications->notifyNewCourseQuestion($course, $question);

        return $question;
    }

    public function queryForCourse(Course $course, ?User $viewer = null): Builder
    {
        $query = $this->questions->query()
            ->where('course_id', $course->id)
            ->with('user')
            ->withCount([
                'answers as approved_answers_count' => function (Builder $builder) {
                    $builder->where('status', QuestionAnswerStatus::Approved);
                },
            ])
            ->latest();

        $query->with([
            'answers' => function ($builder) use ($viewer) {
                $builder->with('user')
                    ->withCount([
                        'votes as helpful_count' => function (Builder $voteBuilder) {
                            $voteBuilder->where('is_helpful', true);
                        },
                    ])
                    ->when($viewer, function (Builder $answerBuilder) use ($viewer) {
                        $answerBuilder->where(function (Builder $visibility) use ($viewer) {
                            $visibility->where('status', QuestionAnswerStatus::Approved)
                                ->orWhere('user_id', $viewer->id);
                        });
                    }, function (Builder $answerBuilder) {
                        $answerBuilder->where('status', QuestionAnswerStatus::Approved);
                    })
                    ->with([
                        'votes' => function ($voteBuilder) use ($viewer) {
                            if (! $viewer) {
                                $voteBuilder->whereRaw('1 = 0');

                                return;
                            }

                            $voteBuilder->where('user_id', $viewer->id)
                                ->where('is_helpful', true);
                        },
                    ])
                    ->orderByDesc('is_accepted')
                    ->latest();
            },
        ]);

        return $query;
    }

    public function isInstructor(Course $course, User $user): bool
    {
        return $course->trainers()->whereKey($user->id)->exists();
    }

    private function getGuestUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'guest@local.invalid'],
            [
                'name' => 'Guest',
                'password' => Hash::make(Str::random(32)),
            ]
        );
    }
}
