<?php

namespace App\Filament\Admin\Resources\Qualifications\Tables;

use App\Models\Qualification;
use App\Services\Admin\QualificationImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class QualificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('name')
                ->label(__('admin.labels.name'))
                ->state(fn (Qualification $record) => $record->getTranslation('name', app()->getLocale()))
                ->searchable(),
            IconColumn::make('status')
                ->label(__('admin.labels.active'))
                ->boolean(fn (Qualification $record) => $record->status === 'active'),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('status')->options([
                'active' => __('admin.options.active'),
                'inactive' => __('admin.options.inactive'),
            ])->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
            Action::make('import_qualifications')
                ->label(__('admin.actions.import_qualifications'))
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (): bool => auth('admin')->user()?->can('settings.create') ?? false)
                ->modalHeading(__('admin.modals.qualification_import.heading'))
                ->modalDescription(__('admin.modals.qualification_import.description'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.qualifications.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                            e(__('admin.actions.download_template')).
                            '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                            e(__('admin.helpers.qualification_import_template_help')).
                            '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label(__('admin.fields.import_file'))
                        ->disk('local')
                        ->directory('imports/qualifications')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = (string) ($data['file'] ?? '');
                    $disk = Storage::disk('local');
                    $summary = app(QualificationImportService::class)->import($disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        __('admin.notifications.import_summary', ['processed' => $summary['processed']]),
                        __('admin.notifications.import_created', ['count' => $summary['created']]),
                        __('admin.notifications.import_skipped', ['count' => $summary['skipped']]),
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 3)) : null,
                    ]));

                    \Filament\Notifications\Notification::make()
                        ->title(__('admin.notifications.qualifications_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.qualifications.heading'))
            ->emptyStateDescription(__('admin.empty_states.qualifications.description'));
    }
}
