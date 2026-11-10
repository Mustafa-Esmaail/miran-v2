<?php

namespace App\Livewire\Home;

use App\Enums\ServiceRequestSource;
use App\Exceptions\BlockedPhoneException;
use App\Exceptions\DuplicateServiceRequestException;
use App\Exceptions\TooManyServiceRequestsException;
use App\Livewire\Concerns\UsesSectionFields;
use App\Livewire\Concerns\VerifiesTurnstile;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Section;
use App\Services\ServiceRequestService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class LeadForm extends Component
{
    use UsesSectionFields;
    use VerifiesTurnstile;

    public string $name = '';

    public string $phone = '';

    public ?int $courseId = null;

    public ?string $message = null;

    public bool $submitted = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $formLayout = 'default';

    public bool $showCourseSelect = true;

    public array $dynamicFields = [];

    public array $formData = [];

    public ?int $typeId = null;

    public ?string $configuredSource = null;

    public string $countryCode = '+966';

    public ?int $lockedCourseId = null;

    public ?int $lockedBundleId = null;

    public ?string $sectionType = null;

    public string $companyFax = '';

    protected function hydrateSectionMeta(Section $section): void
    {
        $this->sectionType = $section->type;
    }

    public function mount(?int $sectionId = null, ?int $lockedCourseId = null, ?int $lockedBundleId = null): void
    {
        $this->sectionId = $sectionId;
        $this->lockedCourseId = $lockedCourseId;
        $this->lockedBundleId = $lockedBundleId;
        $this->loadSectionFields();
        $this->formLayout = $this->fieldValue('layout', 'default');
        $this->showCourseSelect = (bool) $this->fieldValue('show_course_select', true);
        $fields = $this->fieldValue('fields', []);
        $this->dynamicFields = is_array($fields) ? array_values($fields) : [];
        $this->typeId = $this->fieldValue('type_id') ? (int) $this->fieldValue('type_id') : null;
        $this->configuredSource = $this->fieldValue('source') ?: null;

        if ($lockedCourseId) {
            $this->courseId = $lockedCourseId;
        } elseif (! $lockedBundleId && $this->showCourseSelect) {
            $locale = app()->getLocale();
            $this->courseId = Cache::remember("lead_form_courses:{$locale}", 300, function () use ($locale) {
                return Course::query()
                    ->published()
                    ->orderBy("title->{$locale}")
                    ->limit(50)
                    ->get();
            })->first()?->id;
        }
    }

    public function submit(ServiceRequestService $service): void
    {
        $isDynamicLayout = ! in_array($this->formLayout, ['complaint', 'contact'], true) && count($this->dynamicFields) > 0;
        $phoneErrorField = $isDynamicLayout ? 'formData.phone' : 'phone';

        $this->normalizePhoneInputs();

        if ($isDynamicLayout) {
            $this->validateWithTurnstile($this->dynamicValidationRules());
            $payload = $this->payloadFromDynamicFields();
        } elseif (in_array($this->formLayout, ['complaint', 'contact'], true)) {
            $this->validateWithTurnstile([
                'firstName' => ['required', 'string', 'min:2'],
                'lastName' => ['required', 'string', 'min:2'],
                'email' => ['required', 'email'],
                'phone' => ['required', 'regex:'.ServiceRequestService::LOCAL_PHONE_PATTERN],
                'message' => ['nullable', 'string', 'max:500'],
            ]);
            $name = trim($this->firstName.' '.$this->lastName);
            $payload = [
                'name' => $name !== '' ? $name : null,
                'phone' => $this->countryCode.$this->phone,
                'email' => $this->email,
                'message' => $this->message,
            ];
        } else {
            $this->validateWithTurnstile([
                'name' => ['nullable', 'string', 'min:2'],
                'phone' => ['required', 'regex:'.ServiceRequestService::LOCAL_PHONE_PATTERN],
                'courseId' => [$this->requiresCourse() ? 'required' : 'nullable', Rule::exists('courses', 'id')],
                'message' => ['nullable', 'string', 'max:500'],
            ]);
            $payload = [
                'name' => $this->name,
                'phone' => $this->countryCode.$this->phone,
                'course_id' => $this->courseId,
                'bundle_id' => $this->lockedBundleId,
                'message' => $this->message,
            ];
        }

        $source = $this->configuredSource
            ? (ServiceRequestSource::tryFrom($this->configuredSource) ?? ServiceRequestSource::Lead)
            : match ($this->formLayout) {
                'complaint' => ServiceRequestSource::Complaint,
                'contact' => ServiceRequestSource::Contact,
                'corporate' => ServiceRequestSource::CorporateTraining,
                default => ServiceRequestSource::Lead,
            };

        if (blank($payload['phone'] ?? null)) {
            $this->addError($phoneErrorField, __('validation.required', ['attribute' => __('web.home.lead_phone')]));

            return;
        }

        $payload['type_id'] = $this->typeId;
        $payload['ip_address'] = request()->ip();
        $payload['user_agent'] = (string) request()->userAgent();
        $payload['is_suspected_bot'] = filled($this->companyFax);

        try {
            $request = $service->createFromLeadForm($payload, $source, auth()->id(), $this->sectionType);
        } catch (BlockedPhoneException) {
            $this->addError($phoneErrorField, __('web.home.lead_phone_blocked'));

            return;
        } catch (DuplicateServiceRequestException) {
            $this->addError($phoneErrorField, __('web.home.lead_duplicate_today'));

            return;
        } catch (TooManyServiceRequestsException) {
            $this->addError($phoneErrorField, __('web.home.lead_rate_limited'));

            return;
        }

        $this->dispatch('gtm-event', name: 'generate_lead', data: [
            'form' => 'lead_form',
            'source' => $request->source->value,
            'course_id' => $payload['course_id'] ?? null,
            'type_id' => $this->typeId,
        ]);

        $this->submitted = true;

        $this->redirect(route('thank-you'));
    }

    protected function dynamicValidationRules(): array
    {
        $rules = [];

        foreach ($this->dynamicFields as $field) {
            $name = (string) ($field['name'] ?? '');
            $type = (string) ($field['type'] ?? 'text');

            if ($name === '') {
                continue;
            }

            if ($this->isCourseField($name, $type)) {
                continue;
            }

            $fieldRules = [($field['required'] ?? false) ? 'required' : 'nullable'];

            if ($this->isCourseField($name, $type)) {
                $fieldRules[] = 'integer';
                $fieldRules[] = Rule::exists('courses', 'id');
            } elseif ($type === 'email') {
                $fieldRules[] = 'email';
                $fieldRules[] = 'max:255';
            } elseif ($type === 'tel') {
                $fieldRules[] = 'regex:'.ServiceRequestService::LOCAL_PHONE_PATTERN;
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:500';
            }

            $rules["formData.{$name}"] = $fieldRules;
        }

        if ($this->requiresCourse()) {
            $rules['courseId'] = [
                'required',
                'integer',
                Rule::exists('courses', 'id'),
            ];
        }

        return $rules;
    }

    /**
     * Arabic-Indic digits → Latin and +966 / 0 prefixes dropped, so the phone
     * inputs are validated against LOCAL_PHONE_PATTERN like the landing v2 form.
     */
    protected function normalizePhoneInputs(): void
    {
        if ($this->phone !== '') {
            $this->phone = ServiceRequestService::normalizeLocalPhone($this->phone);
        }

        foreach ($this->dynamicFields as $field) {
            $name = (string) ($field['name'] ?? '');

            if (($field['type'] ?? null) === 'tel' && is_string($this->formData[$name] ?? null) && trim($this->formData[$name]) !== '') {
                $this->formData[$name] = ServiceRequestService::normalizeLocalPhone($this->formData[$name]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.regex' => __('web.landing_v2.phone_invalid'),
            'formData.*.regex' => __('web.landing_v2.phone_invalid'),
        ];
    }

    protected function requiresCourse(): bool
    {
        return $this->showCourseSelect && ! $this->lockedBundleId;
    }

    protected function payloadFromDynamicFields(): array
    {
        $name = $this->pickDynamicValue(['name', 'full_name', 'first_name']);
        $phone = $this->pickDynamicValue(['phone', 'mobile', 'phone_number']);
        $email = $this->pickDynamicValue(['email']);
        $message = $this->pickDynamicValue(['message', 'notes', 'note']);
        $courseId = $this->pickDynamicValue(['course_id', 'courseId', 'course']);

        $resolvedPhone = $phone ?: $this->phone;

        return [
            'name' => $name ?: $this->name,
            'phone' => $resolvedPhone !== '' ? $this->countryCode.$resolvedPhone : '',
            'email' => $email ?: null,
            'message' => $message ?: $this->message,
            'course_id' => is_numeric($courseId) ? (int) $courseId : $this->courseId,
            'bundle_id' => $this->lockedBundleId,
        ];
    }

    protected function pickDynamicValue(array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->formData[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function isCourseField(string $name, string $type): bool
    {
        if ($type !== 'select') {
            return false;
        }

        return in_array($name, ['course', 'course_id', 'courseId'], true);
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        if ($this->lockedCourseId) {
            $courses = Course::query()->where('id', $this->lockedCourseId)->get();
        } elseif ($this->lockedBundleId) {
            $courses = collect();
        } else {
            $courses = Cache::remember("lead_form_courses:{$locale}", 300, function () use ($locale) {
                return Course::query()
                    ->published()
                    ->orderBy("title->{$locale}")
                    ->limit(50)
                    ->get();
            });
        }

        $lockedBundle = $this->lockedBundleId
            ? Bundle::query()->find($this->lockedBundleId)
            : null;

        return view('livewire.home.lead-form', [
            'courses' => $courses,
            'lockedBundle' => $lockedBundle,
        ]);
    }
}
