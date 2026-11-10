<?php

namespace App\Filament\Admin\Resources\BroadcastMails\Pages;

use App\Filament\Admin\Resources\BroadcastMails\BroadcastMailResource;
use App\Services\BroadcastMailService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBroadcastMail extends CreateRecord
{
    protected static string $resource = BroadcastMailResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(BroadcastMailService::class)->send($data, auth('admin')->id());
    }

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }
}
