<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public string $classes;

    public function __construct(
        public ?string $href = null,
        public string $variant = 'primary',
        public string $size = 'md',
    ) {
        $base = 'btn-focus inline-flex items-center justify-center rounded-md font-semibold transition';
        $variants = [
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'ghost' => 'btn-ghost',
            'link' => 'btn-link',
        ];
        $sizes = [
            'sm' => 'px-3 py-1.5 text-xs',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-5 py-3 text-base',
        ];
        $variantClass = $variants[$this->variant] ?? $variants['primary'];
        $sizeClass = $sizes[$this->size] ?? $sizes['md'];
        $this->classes = trim($base.' '.$variantClass.' '.$sizeClass);
    }

    public function render(): View
    {
        return view('components.button');
    }
}
