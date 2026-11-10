@props(['attachments'])

@if (!empty($attachments) && $attachments->count() > 0)
    <ul class="grid grid-cols-2 gap-6 sm:grid-cols-4">
        @foreach ($attachments as $attachment)
            <li>
                <a href="{{ route('attachments.view', $attachment) }}" target="_blank" rel="noopener"
                    class="course-profile-attachment-link flex w-full items-center justify-center gap-2 rounded-full bg-[#99745c] px-5 py-2.5 text-sm font-medium text-white shadow-md transition-colors">
                    {{ $attachment->title }}
                </a>
            </li>
        @endforeach
        {{ $slot ?? '' }}
    </ul>
@endif
