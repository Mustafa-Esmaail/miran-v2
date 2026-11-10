<?php

namespace App\View\Components\Site;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Topbar extends Component
{
    public bool $isRtl;

    public string $leftText;

    public string $centerText;

    public string $rightText;

    public function __construct(
        ?string $leftText = null,
        ?string $centerText = null,
        ?string $rightText = null,
    ) {
        $this->isRtl = app()->getLocale() === 'ar';
        $this->leftText = $leftText ?: __('web.layout.commercial_registration');
        $this->centerText = $centerText ?: __('web.home.topbar_message');
        $this->rightText = $rightText ?: __('web.layout.licensing_number');
    }

    public function render(): View
    {
        return view('components.site.topbar');
    }
}
