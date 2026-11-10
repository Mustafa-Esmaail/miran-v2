<?php

namespace App\Livewire;

use App\Enums\CertificateRequestStatus;
use App\Models\CertificateRequest;
use App\Models\Country;
use App\Models\Enrollment;
use App\Models\Qualification;
use Illuminate\View\View;
use Livewire\Component;

class DashboardCertificateForm extends Component
{
    public string $idNumber = '';

    public string $firstNameAr = '';

    public string $secondNameAr = '';

    public string $thirdNameAr = '';

    public string $lastNameAr = '';

    public string $birthDate = '';

    public string $gender = '';

    public string $nationality = '';

    public string $qualification = '';

    public string $email = '';

    public string $phone = '';

    public string $firstNameEn = '';

    public string $secondNameEn = '';

    public string $thirdNameEn = '';

    public string $lastNameEn = '';

    public ?int $courseGroupId = null;

    public bool $submitted = false;

    public bool $isRejected = false;

    public function mount(?int $group = null): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();
        if (! $user) {
            return;
        }

        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';

        if ($group !== null) {
            $this->courseGroupId = $group;
        } else {
            $this->courseGroupId = Enrollment::query()
                ->where('user_id', $user->id)
                ->whereNotNull('course_group_id')
                ->value('course_group_id');
        }

        $this->loadExisting($user->id);
    }

    public function updatedCourseGroupId(): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();
        if (! $user) {
            return;
        }

        $this->loadExisting($user->id);
        $this->submitted = false;
    }

    public function submit(): void
    {
        $this->validate([
            'idNumber' => ['required', 'string', 'max:50'],
            'firstNameAr' => ['required', 'string', 'max:100'],
            'secondNameAr' => ['required', 'string', 'max:100'],
            'thirdNameAr' => ['required', 'string', 'max:100'],
            'lastNameAr' => ['required', 'string', 'max:100'],
            'birthDate' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'nationality' => ['required', 'string', 'max:100'],
            'qualification' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'firstNameEn' => ['required', 'string', 'max:100'],
            'secondNameEn' => ['required', 'string', 'max:100'],
            'thirdNameEn' => ['required', 'string', 'max:100'],
            'lastNameEn' => ['required', 'string', 'max:100'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();
        if (! $user) {
            return;
        }

        $attributes = [
            'id_number' => trim($this->idNumber),
            'first_name_ar' => trim($this->firstNameAr),
            'second_name_ar' => trim($this->secondNameAr) ?: null,
            'third_name_ar' => trim($this->thirdNameAr) ?: null,
            'last_name_ar' => trim($this->lastNameAr),
            'birth_date' => $this->birthDate,
            'gender' => $this->gender,
            'nationality' => trim($this->nationality),
            'qualification' => trim($this->qualification) ?: null,
            'email' => trim($this->email),
            'phone' => trim($this->phone),
            'first_name_en' => trim($this->firstNameEn),
            'second_name_en' => trim($this->secondNameEn),
            'third_name_en' => trim($this->thirdNameEn),
            'last_name_en' => trim($this->lastNameEn),
            'status' => CertificateRequestStatus::Pending,
        ];

        $courseGroupIds = Enrollment::query()
            ->where('user_id', $user->id)
            ->whereNotNull('course_group_id')
            ->pluck('course_group_id')
            ->unique();

        $existingGroupIds = CertificateRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('course_group_id', $courseGroupIds)
            ->pluck('course_group_id');

        $unsubmittedGroupIds = $courseGroupIds->diff($existingGroupIds)
            ->reject(fn (int $courseGroupId): bool => $courseGroupId === $this->courseGroupId);

        foreach ($unsubmittedGroupIds as $courseGroupId) {
            CertificateRequest::query()->updateOrCreate(
                ['user_id' => $user->id, 'course_group_id' => $courseGroupId],
                $attributes
            );
        }

        CertificateRequest::query()->updateOrCreate(
            ['user_id' => $user->id, 'course_group_id' => $this->courseGroupId ?: null],
            $attributes
        );

        $this->isRejected = false;
        $this->submitted = true;
    }

    public function render(): View
    {
        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();
        $locale = app()->getLocale();

        $groups = collect();
        if ($user) {
            $groups = Enrollment::query()
                ->where('user_id', $user->id)
                ->with('courseGroup', 'course')
                ->get()
                ->filter(fn ($e) => $e->courseGroup !== null)
                ->map(fn ($e) => [
                    'id' => $e->courseGroup->id,
                    'label' => ($e->course?->getTranslation('title', $locale) ?? '')
                        .' — '
                        .($e->courseGroup->getTranslation('title', $locale) ?? ('#'.$e->courseGroup->id)),
                ])
                ->unique('id')
                ->values();
        }

        $countries = Country::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Country $c) => [
                $c->getTranslation('name', 'ar') => $c->getTranslation('name', $locale),
            ]);

        $qualifications = Qualification::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Qualification $q) => [
                $q->getTranslation('name', 'ar') => $q->getTranslation('name', $locale),
            ]);

        return view('livewire.dashboard-certificate-form', [
            'groups' => $groups,
            'countries' => $countries,
            'qualifications' => $qualifications,
        ])->layout('layouts.app'); // @phpstan-ignore-line
    }

    private function loadExisting(int $userId): void
    {
        /** @var \App\Models\User|null $user */
        $user = auth('web')->user();

        $existing = CertificateRequest::query()
            ->where('user_id', $userId)
            ->where('course_group_id', $this->courseGroupId ?: null)
            ->first();

        $this->isRejected = $existing?->status === CertificateRequestStatus::Rejected;
        $this->idNumber = $existing?->id_number ?? '';
        $this->firstNameAr = $existing?->first_name_ar ?? '';
        $this->secondNameAr = $existing?->second_name_ar ?? '';
        $this->thirdNameAr = $existing?->third_name_ar ?? '';
        $this->lastNameAr = $existing?->last_name_ar ?? '';
        $this->birthDate = $existing?->birth_date?->format('Y-m-d') ?? '';
        $this->gender = $existing?->gender ?? '';
        $this->nationality = $existing?->nationality ?? '';
        $this->qualification = $existing?->qualification ?? '';
        $this->email = $existing?->email ?? $user?->email ?? '';
        $this->phone = $existing?->phone ?? $user?->phone ?? '';
        $this->firstNameEn = $existing?->first_name_en ?? '';
        $this->secondNameEn = $existing?->second_name_en ?? '';
        $this->thirdNameEn = $existing?->third_name_en ?? '';
        $this->lastNameEn = $existing?->last_name_en ?? '';
    }
}
