<?php

namespace App\Filament\Admin\Resources\ApiClients;

use App\Filament\Admin\Resources\ApiClients\Pages\CreateApiClient;
use App\Filament\Admin\Resources\ApiClients\Pages\EditApiClient;
use App\Filament\Admin\Resources\ApiClients\Pages\ListApiClients;
use App\Filament\Admin\Resources\ApiClients\Schemas\ApiClientForm;
use App\Filament\Admin\Resources\ApiClients\Tables\ApiClientsTable;
use App\Models\ApiClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ApiClientResource extends Resource
{
    protected static ?string $model = ApiClient::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('admin.api_clients.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('admin.api_clients.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.api_clients.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return ApiClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiClientsTable::configure($table);
    }

    /**
     * The plain key is never stored, so this modal is the only time it is shown.
     * Mount it with the key as the "key" argument.
     */
    public static function showKeyAction(): Action
    {
        return Action::make('showApiKey')
            ->modalHeading(__('admin.api_clients.modals.key_heading'))
            ->modalDescription(__('admin.api_clients.modals.key_description'))
            ->modalIcon('heroicon-o-key')
            ->modalIconColor('warning')
            ->fillForm(fn (array $arguments): array => ['api_key' => $arguments['key'] ?? ''])
            ->schema([
                TextInput::make('api_key')
                    ->label(__('admin.api_clients.fields.api_key'))
                    ->readOnly()
                    ->copyable(copyMessage: __('admin.api_clients.notifications.copied'))
                    ->extraInputAttributes(['class' => 'font-mono', 'dir' => 'ltr']),
            ])
            ->modalSubmitActionLabel(__('admin.api_clients.actions.done'))
            ->modalCancelAction(false)
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->cancelParentActions();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiClients::route('/'),
            'create' => CreateApiClient::route('/create'),
            'edit' => EditApiClient::route('/{record}/edit'),
        ];
    }
}
