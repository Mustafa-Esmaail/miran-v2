<?php

namespace App\Filament\Admin\Resources\Page\Actions;

use App\Exceptions\LandingVersionException;
use App\Filament\Admin\Resources\Page\PageResource;
use App\Models\Page;
use App\Services\LandingVersionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Header actions for landing versions: replace the live page with a new
 * version, and open a drafted version through the admin-only preview.
 */
class PublishNewVersionAction
{
    public static function make(): Action
    {
        return Action::make('publish_new_version')
            ->label(__('admin.landing_versions.actions.publish_new_version'))
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('warning')
            ->visible(fn (Page $record): bool => $record->isLiveVersion()
                && (auth('admin')->user()?->can('landing_versions.publish') ?? false))
            ->modalDescription(fn (Page $record): string => __('admin.landing_versions.helpers.publish_new_version', [
                'slug' => $record->slug,
                'draft' => app(LandingVersionService::class)->draftSlug($record),
            ]))
            ->schema(fn (Page $record): array => [
                Select::make('replacement_page_id')
                    ->label(__('admin.landing_versions.labels.replacement_page'))
                    ->helperText(__('admin.landing_versions.helpers.replacement_page'))
                    ->options(fn (): array => app(LandingVersionService::class)->replacementOptions($record, app()->getLocale()))
                    ->searchable()
                    ->required(),
                TextInput::make('version_name')
                    ->label(__('admin.landing_versions.labels.version_name'))
                    ->placeholder('v'.((int) Page::query()->where('landing_key', $record->landing_key)->max('version') + 1))
                    ->maxLength(255),
                TextInput::make('marketing_source')
                    ->label(__('admin.landing_versions.labels.marketing_source'))
                    ->default($record->marketing_source)
                    ->maxLength(255),
            ])
            ->action(function (array $data, Page $record, Action $action): void {
                $replacement = Page::query()->findOrFail($data['replacement_page_id']);

                try {
                    $published = app(LandingVersionService::class)->publishNewVersion(
                        $record,
                        $replacement,
                        $data['version_name'] ?? null,
                        $data['marketing_source'] ?? null,
                    );
                } catch (LandingVersionException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();
                    $action->halt();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('admin.landing_versions.notifications.published', ['version' => $published->versionLabel()]))
                    ->send();

                $action->redirect(PageResource::getUrl('view', ['record' => $published]));
            });
    }

    public static function preview(): Action
    {
        return Action::make('preview_version')
            ->label(__('admin.landing_versions.actions.preview'))
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->visible(fn (Page $record): bool => $record->isVersioned())
            ->url(fn (Page $record): string => route('admin.pages.preview', $record))
            ->openUrlInNewTab();
    }
}
