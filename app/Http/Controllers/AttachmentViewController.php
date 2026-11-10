<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentVisibility;
use App\Models\CourseAttachment;
use App\Support\S3Url;
use Illuminate\View\View;

class AttachmentViewController extends Controller
{
    public function __invoke(CourseAttachment $attachment): View
    {
        abort_unless(in_array($attachment->visibility, [AttachmentVisibility::Public, AttachmentVisibility::Profile], true), 404);

        $path = collect($attachment->file_path)->first();
        abort_unless($path, 404);

        $fileUrl = S3Url::temporary($path, 30);
        abort_unless($fileUrl, 404);

        $course = $attachment->course;

        return view('pages.pdf-viewer', [
            'title' => $attachment->title,
            'fileUrl' => $fileUrl,
            'ctaLabel' => __('web.course_profile.default_cta_label'),
            'ctaUrl' => $course ? route('courses.show', $course->slug) : null,
        ]);
    }
}
