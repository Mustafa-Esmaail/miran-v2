<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    public string $locale = 'ar';

    public string $variant = 'inline';

    public function mount(string $variant = 'inline'): void
    {
        $this->variant = $variant;
        $this->locale = session('app_locale') ?? app()->getLocale();
    }

    public function setLocale(string $locale): void
    {
        if (! in_array($locale, ['en', 'ar'], true)) {
            return;
        }

        $this->locale = $locale;
        session(['app_locale' => $locale, 'admin_locale' => $locale]);
        app()->setLocale($locale);

        if ($user = Auth::guard('web')->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        if ($admin = Auth::guard('admin')->user()) {
            $admin->forceFill(['locale' => $locale])->save();
        }

        $this->redirect(url()->previous(), navigate: false);
    }

    public function render()
    {
        return view('livewire.locale-switcher');
    }
}
