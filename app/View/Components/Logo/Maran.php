<?php

namespace App\View\Components\Logo;

use App\Services\Settings\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Maran extends Component
{
    public ?string $logoUrl;

    public string $siteTitle;

    public function __construct(SettingsService $settingsService)
    {
        $this->logoUrl = $settingsService->logoUrl();
        $this->siteTitle = $settingsService->siteTitle(app()->getLocale()) ?: __('web.layout.title');
    }

    public function render(): View
    {
        return view('components.logo.maran');
    }
}
