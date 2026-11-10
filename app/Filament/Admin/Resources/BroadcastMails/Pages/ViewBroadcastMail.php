<?php

namespace App\Filament\Admin\Resources\BroadcastMails\Pages;

use App\Filament\Admin\Resources\BroadcastMails\BroadcastMailResource;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewBroadcastMail extends ViewRecord
{
    protected static string $resource = BroadcastMailResource::class;

    public function getBreadcrumb(): string
    {
        return __('admin.pages.view');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.view_title', ['label' => $this->getRecordTitle()]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['creator_name'] = $this->getRecord()->creator?->name;

        return $data;
    }

    /**
     * Recipients are shown by RecipientsRelationManager below, so this
     * view-only schema replaces the resource's create form (which has a
     * `user_ids` select with no backing column) instead of reusing it disabled.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.send_mail'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('subject')->label(__('admin.fields.subject'))->disabled()->columnSpanFull(),
                    TextInput::make('status')
                        ->label(__('admin.labels.status'))
                        ->disabled()
                        ->formatStateUsing(fn (string $state): string => __('admin.options.'.$state) !== 'admin.options.'.$state
                            ? __('admin.options.'.$state)
                            : $state),
                    TextInput::make('creator_name')->label(__('admin.labels.created_by'))->disabled(),
                    RichEditor::make('body')->label(__('admin.fields.body'))->disabled()->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
