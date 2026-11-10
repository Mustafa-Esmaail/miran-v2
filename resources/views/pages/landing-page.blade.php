<x-layouts.app :meta-title="$metaTitle ?? null" :meta-description="$metaDescription ?? null" :og-image-url="$ogImageUrl ?? null"
    :whatsapp-message="($landable ?? null) ? __('web.floating_contact.course_message', ['title' => $landable->title]) : null">
    @if (!empty($sections) && $sections->count() > 0)
        @foreach ($sections as $section)
            @includeIf("sections.{$section->type}", ['section' => $section, 'landable' => $landable ?? null])
        @endforeach
    @endif
</x-layouts.app>
