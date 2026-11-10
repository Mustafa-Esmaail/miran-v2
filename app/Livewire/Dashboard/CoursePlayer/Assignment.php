<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Enums\AssignmentSubmissionStatus;
use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Models\AssignmentSubmission;
use App\Services\CourseProgressionService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Assignment extends Component
{
    use InteractsWithCoursePlayer, WithFileUploads;

    public string $course_slug;

    public int $group;

    public int $assignment;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $newFile = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $files = [];

    public function mount(string $course_slug, int $group, int $assignment): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
        $this->assignment = $assignment;

        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $assignmentRecord = $this->resolveAssignment($enrollment, $this->assignment);
        $progression = app(CourseProgressionService::class);

        if (! $progression->isAssignmentAccessible($enrollment, $assignmentRecord)) {
            session()->flash('status', __('web.course_player.locked_item_message'));
            $firstAccessibleDay = $progression->firstAccessibleDay($enrollment);

            if ($firstAccessibleDay) {
                $this->redirectRoute('dashboard.course-player.day', [$this->course_slug, $this->group, $firstAccessibleDay->id]);

                return;
            }

            $this->redirectRoute('dashboard.course-player', [$this->course_slug, $this->group]);
        }
    }

    protected function rules(): array
    {
        return [
            'newFile' => ['nullable', 'file', 'max:20480', 'mimes:pdf,xls,xlsx,csv,doc,docx,ppt,pptx,jpg,jpeg,png,gif,webp'],
            'files' => ['required', 'array', 'min:1'],
        ];
    }

    public function updatedNewFile(): void
    {
        $this->validateOnly('newFile');

        if ($this->newFile) {
            $this->files[] = $this->newFile;
        }

        $this->reset('newFile');
    }

    public function removeFile(int $index): void
    {
        unset($this->files[$index]);
        $this->files = array_values($this->files);
    }

    public function submit(): void
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $assignmentRecord = $this->resolveAssignment($enrollment, $this->assignment);

        $alreadySubmitted = AssignmentSubmission::query()
            ->where('assignment_id', $assignmentRecord->id)
            ->where('enrollment_id', $enrollment->id)
            ->exists();

        if ($alreadySubmitted) {
            return;
        }

        $this->validate(['files' => ['required', 'array', 'min:1']]);

        $paths = collect($this->files)
            ->map(fn ($file) => $file->store('assignment-submissions/'.$enrollment->id, 's3'))
            ->values()
            ->all();

        AssignmentSubmission::create([
            'assignment_id' => $assignmentRecord->id,
            'enrollment_id' => $enrollment->id,
            'file_path' => $paths,
            'submitted_at' => now(),
            'status' => AssignmentSubmissionStatus::Submitted,
        ]);

        $this->reset('files', 'newFile');
        session()->flash('status', __('web.course_player.assignment_submit'));
    }

    public function render()
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $assignmentRecord = $this->resolveAssignment($enrollment, $this->assignment);
        $progression = app(CourseProgressionService::class);

        $day = $this->resolveDay($enrollment, (int) $assignmentRecord->course_curriculum_section_id);
        $items = $this->buildDayItemsCollection($enrollment, $day, $progression);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignmentRecord->id)
            ->where('enrollment_id', $enrollment->id)
            ->first();

        return view('livewire.dashboard.course-player.assignment', [
            'course' => $this->buildCourseMeta($enrollment),
            'day' => [
                'id' => $day->id,
                'title' => $day->getTranslation('title', app()->getLocale()),
                'route' => route('dashboard.course-player.day', [$this->course_slug, $this->group, $day->id]),
            ],
            'assignmentData' => [
                'id' => $assignmentRecord->id,
                'title' => $assignmentRecord->getTranslation('title', app()->getLocale()),
                'description' => $assignmentRecord->description,
                'due_at' => $assignmentRecord->due_at,
                'max_grade' => $assignmentRecord->max_grade,
                'reference_files' => $this->assignmentFiles($assignmentRecord->attachment_path),
            ],
            'submission' => $submission,
            'submissionFiles' => $submission ? $this->assignmentFiles($submission->file_path) : [],
            'navLinks' => $this->buildDayNavLinks($enrollment, $day, $items, $progression),
        ])->layout('layouts.app');
    }
}
