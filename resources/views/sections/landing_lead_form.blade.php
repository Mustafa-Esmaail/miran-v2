@php
    $lockedCourseId = (isset($landable) && $landable instanceof \App\Models\Course) ? $landable->id : null;
    $lockedBundleId = (isset($landable) && $landable instanceof \App\Models\Bundle) ? $landable->id : null;
@endphp

<livewire:home.lead-form
    :section-id="$section->id"
    :locked-course-id="$lockedCourseId"
    :locked-bundle-id="$lockedBundleId"
/>
