<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class DashboardProfile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public ?string $locale = null;

    public ?string $phone = null;

    public ?string $national_id = null;

    public ?string $nationality = null;

    public ?string $birth_date = null;

    public ?string $gender = null;

    public ?string $about = null;

    public $avatar;

    public ?string $avatar_path = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user?->name ?? '';
        $this->email = $user?->email ?? '';
        $this->locale = $user?->locale;
        $this->phone = $user?->phone;
        $this->national_id = $user?->national_id;
        $this->nationality = $user?->nationality;
        $this->birth_date = $user?->birth_date?->format('Y-m-d');
        $this->gender = $user?->gender;
        $this->about = $user?->about;
        $this->avatar_path = $user?->avatar_path;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.auth()->id()],
            'locale' => ['nullable', 'in:en,ar'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'about' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        if (is_impersonating()) {
            $this->dispatch('toast', message: __('web.profile.impersonating_blocked'));

            return;
        }

        $this->validate();

        $user = auth()->user();
        $disk = $this->avatarDisk();
        $avatarPath = $user?->avatar_path;
        if ($this->avatar) {
            $path = $this->avatar->storePublicly('avatars', $disk);
            if ($user?->avatar_path) {
                Storage::disk($disk)->delete($user->avatar_path);
            }
            $avatarPath = $path;
        }

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'phone' => $this->phone,
            'national_id' => $this->national_id,
            'nationality' => $this->nationality,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'about' => $this->about,
            'avatar_path' => $avatarPath,
        ]);

        $this->avatar = null;
        $this->avatar_path = $avatarPath;
        session()->flash('status', __('web.profile.saved'));
        session()->flash('avatar_path', $avatarPath);
        $this->dispatch('toast', message: __('web.profile.saved'));
    }

    private function avatarDisk(): string
    {
        $disk = (string) config('filesystems.default', 'public');
        if ($disk === 's3' && ! config('filesystems.disks.s3.bucket')) {
            return 'public';
        }

        return $disk;
    }

    public function render(): View
    {
        return view('livewire.dashboard-profile')->layout('layouts.app');
    }
}
