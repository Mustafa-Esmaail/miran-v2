<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BannerTitle extends Component
{
    public function __construct(public string $title) {}

    public function render(): View
    {
        return view('components.banner-title');
    }
}
