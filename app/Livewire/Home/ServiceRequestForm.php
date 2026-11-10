<?php

namespace App\Livewire\Home;

use App\Enums\ServiceRequestSource;
use App\Exceptions\BlockedPhoneException;
use App\Exceptions\DuplicateServiceRequestException;
use App\Exceptions\TooManyServiceRequestsException;
use App\Livewire\Concerns\UsesSectionFields;
use App\Models\Course;
use App\Services\ServiceRequestService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class ServiceRequestForm extends Component
{
    use UsesSectionFields;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public ?string $message = null;

    public ?int $courseId = null;

    public bool $submitted = false;

    public bool $showCourseSelect = false;

    public bool $showEmail = true;

    public bool $showMessage = true;

    public ?int $typeId = null;

    public string $companyFax = '';

    public function mount(?int $sectionId = null): void
    {
        $this->sectionId = $sectionId;
        $this->loadSectionFields();
        $this->typeId = $this->fieldValue('type_id') ? (int) $this->fieldValue('type_id') : null;
        $this->showCourseSelect = (bool) $this->fieldValue('show_course_select', false);
        $this->showEmail = (bool) $this->fieldValue('show_email', true);
        $this->showMessage = (bool) $this->fieldValue('show_message', true);
    }

    public function submit(ServiceRequestService $service): void
    {
        $this->phone = ServiceRequestService::normalizeLocalPhone($this->phone);

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:190'],
            'phone' => ['required', 'regex:'.ServiceRequestService::LOCAL_PHONE_PATTERN],
        ];

        if ($this->showEmail) {
            $rules['email'] = ['nullable', 'email', 'max:190'];
        }

        if ($this->showMessage) {
            $rules['message'] = ['nullable', 'string', 'max:1000'];
        }

        if ($this->showCourseSelect) {
            $rules['courseId'] = ['nullable', Rule::exists('courses', 'id')];
        }

        $this->validate($rules);

        $payload = [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->showEmail ? $this->email : null,
            'message' => $this->showMessage ? $this->message : null,
            'course_id' => $this->showCourseSelect ? $this->courseId : null,
            'type_id' => $this->typeId,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'is_suspected_bot' => filled($this->companyFax),
        ];

        try {
            $service->createFromForm($payload, ServiceRequestSource::Form, auth()->id());
        } catch (BlockedPhoneException) {
            $this->addError('phone', __('web.home.lead_phone_blocked'));

            return;
        } catch (DuplicateServiceRequestException) {
            $this->addError('phone', __('web.home.lead_duplicate_today'));

            return;
        } catch (TooManyServiceRequestsException) {
            $this->addError('phone', __('web.home.lead_rate_limited'));

            return;
        }

        $this->dispatch('gtm-event', name: 'generate_lead', data: [
            'form' => 'service_request',
            'course_id' => $this->showCourseSelect ? $this->courseId : null,
            'type_id' => $this->typeId,
        ]);

        $this->submitted = true;
        $this->reset(['name', 'phone', 'email', 'message', 'courseId']);
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.regex' => __('web.landing_v2.phone_invalid'),
        ];
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        return view('livewire.home.service-request-form', [
            'courses' => $this->showCourseSelect
                ? Cache::remember("service_request_form_courses:{$locale}", 300, function () use ($locale) {
                    return Course::query()
                        ->published()
                        ->orderBy("title->{$locale}")
                        ->limit(50)
                        ->get();
                })
                : collect(),
        ]);
    }
}
