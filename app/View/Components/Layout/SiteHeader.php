<?php

namespace App\View\Components\Layout;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteHeader extends Component
{
    public function __construct(
        public ?string $logoUrl = null,
        public string $brand = 'Miran',
        public array $navLinks = [],
        public int $cartCount = 0,
        public ?string $topbarLeft = null,
        public ?string $topbarCenter = null,
        public ?string $topbarRight = null,
    ) {
        $this->topbarLeft = $this->topbarLeft ?? __('web.layout.commercial_registration');
        $this->topbarCenter = $this->topbarCenter ?? __('web.home.topbar_message');
        $this->topbarRight = $this->topbarRight ?? __('web.layout.licensing_number');
    }

    public function render(): View
    {
        return view('components.layout.site-header');
    }
}
