<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class DashboardDeleteAccount extends Component
{
    public bool $confirming = false;

    public function confirmDelete(): void
    {
        $this->confirming = true;
    }

    public function cancelDelete(): void
    {
        $this->confirming = false;
    }

    public function deleteAccount(): ?RedirectResponse
    {
        if (is_impersonating()) {
            $this->confirming = false;
            session()->flash('status', __('web.account.impersonating_blocked'));

            return null;
        }

        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $user->update(['status' => User::STATUS_DELETED]);

        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', __('web.account.deleted'));
    }

    public function render(): View
    {
        return view('livewire.dashboard-delete-account')->layout('layouts.app');
    }
}
