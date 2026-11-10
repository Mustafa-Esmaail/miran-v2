@props(['ctaLabel' => null, 'ctaUrl' => null])

@if ($ctaUrl)
    <a
        href="{{ $ctaUrl }}"
        class="course-profile-cta flex w-full items-center justify-center gap-2 rounded-full bg-[#99745c] px-5 py-2.5 text-sm font-medium text-white shadow-md transition-colors"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M3 3h1.5l1.6 9.6a2 2 0 002 1.65h8.55a2 2 0 002-1.65L20 8H6" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="9" cy="19.5" r="1.25" fill="white" />
            <circle cx="17" cy="19.5" r="1.25" fill="white" />
        </svg>
        {{ $ctaLabel }}
    </a>

    <style>
        @keyframes course-profile-cta-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .course-profile-cta {
            animation: course-profile-cta-pulse 1.8s ease-in-out infinite;
        }

        .course-profile-cta:hover {
            animation-play-state: paused;
            transform: scale(1.1);
            background-color: #7d5f49;
        }
    </style>
@endif