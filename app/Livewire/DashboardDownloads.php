<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\CourseAttachment;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Support\S3Url;
use Illuminate\View\View;
use Livewire\Component;

class DashboardDownloads extends Component
{
    public function render(): View
    {
        $courseIds = Enrollment::query()
            ->where('user_id', auth()->id())
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values();

        $courseAttachments = CourseAttachment::query()
            ->whereIn('course_id', $courseIds)
            ->with('course')
            ->get()
            ->filter(function (CourseAttachment $attachment) {
                $path = is_array($attachment->file_path) ? ($attachment->file_path[0] ?? null) : $attachment->file_path;
                if (! $path) {
                    return false;
                }
                $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

                return $extension === 'pdf';
            })
            ->map(function (CourseAttachment $attachment) {
                $path = is_array($attachment->file_path) ? ($attachment->file_path[0] ?? null) : $attachment->file_path;

                return [
                    'title' => $attachment->getTranslation('title', app()->getLocale()),
                    'course_title' => $attachment->course?->getTranslation('title', app()->getLocale()) ?? '',
                    'type' => __('web.downloads.types.course_attachment'),
                    'url' => $path ? S3Url::temporary((string) $path) : null,
                    'created_at' => $attachment->created_at?->format('d/m/Y'),
                ];
            });

        $lessonPdfs = Lesson::query()
            ->whereIn('course_id', $courseIds)
            ->whereNotNull('pdf_path')
            ->with('course')
            ->get()
            ->map(function (Lesson $lesson) {
                return [
                    'title' => $lesson->getTranslation('title', app()->getLocale()),
                    'course_title' => $lesson->course?->getTranslation('title', app()->getLocale()) ?? '',
                    'type' => __('web.downloads.types.lesson_pdf'),
                    'url' => S3Url::temporary($lesson->pdf_path),
                    'created_at' => $lesson->created_at?->format('d/m/Y'),
                ];
            });

        $certificates = Certificate::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('file_path')
            ->with('course')
            ->get()
            ->filter(function (Certificate $certificate) {
                $path = is_array($certificate->file_path) ? ($certificate->file_path[0] ?? null) : $certificate->file_path;
                if (! $path) {
                    return false;
                }
                $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

                return $extension === 'pdf';
            })
            ->map(function (Certificate $certificate) {
                $path = is_array($certificate->file_path) ? ($certificate->file_path[0] ?? null) : $certificate->file_path;

                return [
                    'title' => __('web.downloads.certificate_title'),
                    'course_title' => $certificate->course?->getTranslation('title', app()->getLocale()) ?? '',
                    'type' => __('web.downloads.types.certificate'),
                    'url' => $path ? S3Url::temporary((string) $path) : null,
                    'created_at' => $certificate->approved_at?->format('d/m/Y') ?? $certificate->created_at?->format('d/m/Y'),
                ];
            });

        $downloads = collect()
            ->merge($courseAttachments)
            ->merge($lessonPdfs)
            ->merge($certificates)
            ->sortByDesc('created_at')
            ->values();

        return view('livewire.dashboard-downloads', ['downloads' => $downloads])
            ->layout('layouts.app');
    }
}
