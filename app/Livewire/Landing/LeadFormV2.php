<?php

namespace App\Livewire\Landing;

use App\Enums\ServiceRequestSource;
use App\Exceptions\BlockedPhoneException;
use App\Exceptions\DuplicateServiceRequestException;
use App\Exceptions\TooManyServiceRequestsException;
use App\Livewire\Concerns\UsesSectionFields;
use App\Livewire\Concerns\VerifiesTurnstile;
use App\Services\ServiceRequestService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Phone-only lead form of the landing v2 hero (sections/landing_v2_hero).
 * Same pipeline as the other lead forms: ServiceRequestService, anti-spam
 * guards, GTM generate_lead event, then the thank-you page.
 */
class LeadFormV2 extends Component
{
    use UsesSectionFields;
    use VerifiesTurnstile;

    public const COUNTRY_CODE = '+966';

    public string $phone = '';

    public ?int $lockedCourseId = null;

    public ?int $lockedBundleId = null;

    public ?int $typeId = null;

    public string $companyFax = '';

    /** Slug of the landing page, sent to the Google Sheet only (not stored). Locked so it can't be edited from the browser. */
    #[Locked]
    public ?string $landingSlug = null;

    /** Landing page (version) the lead is counted for. Locked so it can't be edited from the browser. */
    #[Locked]
    public ?int $pageId = null;

    public function mount(?int $sectionId = null, ?int $lockedCourseId = null, ?int $lockedBundleId = null, ?string $landingSlug = null, ?int $pageId = null): void
    {
        $this->landingSlug = filled($landingSlug) ? $landingSlug : null;
        $this->pageId = $pageId;
        $this->sectionId = $sectionId;
        $this->lockedCourseId = $lockedCourseId;
        $this->lockedBundleId = $lockedBundleId;
        $this->loadSectionFields();
        $this->typeId = $this->fieldValue('type_id') ? (int) $this->fieldValue('type_id') : null;
    }

    public function submit(ServiceRequestService $service): void
    {
        $this->phone = self::normalizeLocalPhone($this->phone);

        $this->validateWithTurnstile([
            'phone' => ['required', 'regex:'.ServiceRequestService::LOCAL_PHONE_PATTERN],
        ]);

        $payload = [
            'phone' => self::COUNTRY_CODE.$this->phone,
            'course_id' => $this->lockedCourseId,
            'bundle_id' => $this->lockedBundleId,
            'page_id' => $this->pageId,
            'type_id' => $this->typeId,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
            'is_suspected_bot' => filled($this->companyFax),
        ];

        try {
            $request = $service->createFromLeadForm($payload, ServiceRequestSource::LandingPage, auth()->id(), null, $this->landingSlug);
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
            'form' => 'landing_v2',
            'source' => $request->source->value,
            'course_id' => $this->lockedCourseId,
            'bundle_id' => $this->lockedBundleId,
            'type_id' => $this->typeId,
        ]);

        $this->redirect(route('thank-you'));
    }

    /**
     * Arabic-Indic digits → Latin, drops a leading 966 / 0, keeps the 9
     * national digits ("51 234 5678" → "5XXXXXXXX").
     */
    public static function normalizeLocalPhone(string $phone): string
    {
        return ServiceRequestService::normalizeLocalPhone($phone);
    }

    protected function messages(): array
    {
        return [
            'phone.regex' => __('web.landing_v2.phone_invalid'),
        ];
    }

    public function render(): View
    {
        return view('livewire.landing.lead-form-v2', [
            'submitText' => $this->fieldText('submit_text', app()->getLocale(), __('web.landing_v2.submit')),
        ]);
    }
}
