<?php

namespace App\View\Components;

use App\Support\S3Url;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AccountShell extends Component
{
    public ?string $userName;

    public ?string $userId;

    public ?string $avatarUrl;

    public string $heroTitle;

    public string $displayTitle;

    public string $userInitial;

    public function __construct(
        public ?string $active = null,
        public ?string $title = null,
        public ?string $subtitle = null,
        public bool $showNav = true,
        public ?array $navLinks = null,
        public bool $showHero = true,
        public bool $showHeader = true,
        public bool $showSidebarChat = false,
        public ?int $chatCourseGroupId = null,
    ) {
        $user = auth('web')->user();
        $this->userName = $user?->name;
        $this->userId = $user?->id ? (string) $user->id : null;
        $this->avatarUrl = $user?->avatar_path ? S3Url::temporary($user->avatar_path) : null;
        $this->userInitial = $this->userName ? strtoupper(mb_substr($this->userName, 0, 1)) : 'U';
        $this->heroTitle = __('web.account.title');
        $this->displayTitle = $this->title ?? __('web.account.settings');
    }

    public function render(): View
    {
        return view('components.account-shell');
    }
}
