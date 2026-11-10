<?php

namespace App\Livewire;

use App\Models\CertificateRequest;
use App\Models\Enrollment;
use Illuminate\View\View;
use Livewire\Component;

class DashboardCertificates extends Component
{
    public function render(): View
    {
        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();
        $locale = app()->getLocale();

        $rows = collect();

        if ($user) {
            $enrollments = Enrollment::query()
                ->where('user_id', $user->id)
                ->with('course', 'courseGroup')
                ->get();

            $groupIds = $enrollments->pluck('course_group_id')->filter()->unique()->values();

            $requests = CertificateRequest::query()
                ->where('user_id', $user->id)
                ->whereIn('course_group_id', $groupIds)
                ->get()
                ->keyBy('course_group_id');

            $rows = $enrollments->map(function (Enrollment $enrollment) use ($locale, $requests) {
                $groupId = $enrollment->course_group_id;
                $request = $groupId ? ($requests[$groupId] ?? null) : null;

                return [
                    'course_title'    => $enrollment->course?->getTranslation('title', $locale) ?? '—',
                    'group_title'     => $enrollment->courseGroup?->getTranslation('title', $locale) ?? '—',
                    'course_group_id' => $groupId,
                    'submitted'       => $request !== null,
                    'submitted_at'    => $request?->updated_at?->format('d/m/Y'),
                    'apply_url'       => route('dashboard.certificate-form', $groupId ? ['group' => $groupId] : []),
                ];
            });
        }

        return view('livewire.dashboard-certificates', ['rows' => $rows])
            ->layout('layouts.app'); // @phpstan-ignore-line
    }
}
