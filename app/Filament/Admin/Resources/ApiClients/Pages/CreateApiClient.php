<?php

namespace App\Filament\Admin\Resources\ApiClients\Pages;

use App\Filament\Admin\Resources\ApiClients\ApiClientResource;
use App\Services\Api\ApiKeyService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApiClient extends CreateRecord
{
    protected static string $resource = ApiClientResource::class;

    /**
     * Protected so Livewire never serializes the plain key to the browser state.
     */
    protected ?string $plainKey = null;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $key = app(ApiKeyService::class)->generate();
        $this->plainKey = $key['plain'];

        return [
            ...$data,
            'key_hash' => $key['key_hash'],
            'key_prefix' => $key['key_prefix'],
            'created_by' => auth('admin')->id(),
        ];
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    /**
     * Stay on the page (the record is already committed) so the key modal can open;
     * closing the modal redirects to the list.
     */
    protected function afterCreate(): void
    {
        Notification::make()->title(__('admin.api_clients.notifications.created'))->success()->send();

        $this->mountAction('showApiKey', ['key' => $this->plainKey]);

        $this->halt();
    }

    public function showApiKeyAction(): Action
    {
        return ApiClientResource::showKeyAction()
            ->action(fn () => $this->redirect(static::getResource()::getUrl('index')));
    }
}
