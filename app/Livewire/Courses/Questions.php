<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\QuestionAnswer;
use App\Repositories\Contracts\CourseQuestionRepositoryInterface;
use App\Services\AnswerService;
use App\Services\QuestionService;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Questions extends Component
{
    use WithPagination;

    public int $courseId;

    public ?int $selectedQuestionId = null;

    public string $questionTitle = '';

    public string $questionContent = '';

    public string $guestName = '';

    public string $guestEmail = '';

    public string $answerContent = '';

    public int $perPage = 5;

    public function mount(int $courseId): void
    {
        $this->courseId = $courseId;
    }

    public function updatingPage(): void
    {
        $this->selectedQuestionId = null;
        $this->resetValidation();
    }

    public function selectQuestion(int $questionId): void
    {
        $this->selectedQuestionId = $questionId;
        $this->resetValidation();
        $this->reset('answerContent');
    }

    public function submitQuestion(QuestionService $questions): void
    {
        $rules = [
            'questionTitle' => ['required', 'string', 'min:5', 'max:120'],
            'questionContent' => ['required', 'string', 'min:10', 'max:2000'],
        ];

        if (! auth()->check()) {
            $rules['guestName'] = ['required', 'string', 'min:2', 'max:80'];
            $rules['guestEmail'] = ['nullable', 'email', 'max:255'];
        }

        $this->validate($rules);

        $course = Course::query()->published()->findOrFail($this->courseId);

        $questions->createQuestion(
            $course,
            auth()->user(),
            $this->questionTitle,
            $this->questionContent,
            $this->guestName !== '' ? $this->guestName : null,
            $this->guestEmail !== '' ? $this->guestEmail : null
        );

        $this->reset('questionTitle', 'questionContent', 'guestName', 'guestEmail');
        session()->flash('status', __('web.questions.submitted'));
        $this->dispatch('toast', message: __('web.questions.submitted'));
    }

    public function submitAnswer(
        AnswerService $answers,
        CourseQuestionRepositoryInterface $questions
    ): void {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $this->validate([
            'answerContent' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $question = $this->getSelectedQuestion($questions);
        if (! $question) {
            $this->addError('answerContent', __('web.questions.invalid_question'));

            return;
        }

        $answers->createAnswer($question, auth()->user(), $this->answerContent);

        $this->reset('answerContent');
        session()->flash('status', __('web.questions.answer_submitted'));
        $this->dispatch('toast', message: __('web.questions.answer_submitted'));
    }

    public function markHelpful(AnswerService $answers, int $answerId): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $answer = QuestionAnswer::query()->find($answerId);
        if (! $answer) {
            return;
        }

        $answers->toggleHelpful($answer, auth()->user());
    }

    public function acceptAnswer(AnswerService $answers, int $answerId): void
    {
        if (! auth()->check()) {
            redirect()->guest(route('login'));

            return;
        }

        $answer = QuestionAnswer::query()->find($answerId);
        if (! $answer) {
            return;
        }

        $answers->acceptAnswer($answer, auth()->user());
    }

    public function render(QuestionService $questions): View
    {
        $course = Course::query()->published()->findOrFail($this->courseId);

        $paginator = $questions->queryForCourse($course, auth()->user())
            ->paginate($this->perPage);
        $items = $paginator->getCollection();

        if (! $this->selectedQuestionId && $items->isNotEmpty()) {
            $this->selectedQuestionId = $items->first()->id;
        }

        $selectedQuestion = $items->firstWhere('id', $this->selectedQuestionId);

        return view('livewire.courses.questions', [
            'course' => $course,
            'paginator' => $paginator,
            'questions' => $items,
            'selectedQuestion' => $selectedQuestion,
            'canAsk' => true,
            'isInstructor' => auth()->check() ? $questions->isInstructor($course, auth()->user()) : false,
        ]);
    }

    private function getSelectedQuestion(CourseQuestionRepositoryInterface $questions): ?CourseQuestion
    {
        if (! $this->selectedQuestionId) {
            return null;
        }

        $question = $questions->findById($this->selectedQuestionId);
        if (! $question || $question->course_id !== $this->courseId) {
            return null;
        }

        return $question;
    }
}
