<x-mail::message>
@if (filled($siteLogoUrl))
<div style="margin-bottom: 16px;">
    <img src="{{ $siteLogoUrl }}" alt="{{ $appName }}" style="max-height: 56px; width: auto;">
</div>
@endif

# {{ $greeting }}

{{ $statusLine }}

@if (filled($invitedByLine))
{{ $invitedByLine }}

@endif
@if (filled($plainPassword))
{{ $emailLine }}

{{ $passwordLine }}

@endif
@if (filled($courseTitle))
**{{ $courseTitleLabel }}:** {{ $courseTitle }}
@endif

**{{ $enrollmentDateLabel }}:** {{ $enrollmentDate }}

@if (filled($courseImageUrl))
<div style="margin-top: 12px;">
    <img src="{{ $courseImageUrl }}" alt="{{ $courseTitle ?? $appName }}" style="max-width: 100%; border-radius: 10px;">
</div>
@endif

{{ $loginHint }}

@if (filled($securityNote))
{{ $securityNote }}
@endif

<x-mail::button :url="$loginActionUrl">
{{ $loginActionText }}
</x-mail::button>

@if (filled($trainingVideoUrl))
## {{ $trainingVideoLabel }}

{{ $trainingVideoDescription }}

<x-mail::button :url="$trainingVideoUrl" color="success">
{{ $trainingVideoActionText }}
</x-mail::button>
@endif

@if (filled($certificateFormUrl))
## {{ $certificateFormLabel }}

{{ $certificateFormDescription }}

<x-mail::button :url="$certificateFormUrl" color="primary">
{{ $certificateFormActionText }}
</x-mail::button>
@endif

{{ $salutation }}
</x-mail::message>
