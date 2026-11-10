<?php

namespace App\Filament\Admin\Resources\BroadcastMails\Schemas;

use App\Models\User;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BroadcastMailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.send_mail'))
                ->columnSpanFull()
                ->schema([
                    Select::make('user_ids')
                        ->label(__('admin.fields.recipients'))
                        ->multiple()
                        ->searchable()
                        ->required()
                        ->getSearchResultsUsing(fn (string $search): array => User::query()
                            ->where('status', User::STATUS_ACTIVE)
                            ->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"))
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                            ->all())
                        ->getOptionLabelsUsing(fn (array $values): array => User::query()
                            ->whereIn('id', $values)
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                            ->all())
                        ->columnSpanFull(),
                    TextInput::make('subject')
                        ->label(__('admin.fields.subject'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    RichEditor::make('body')
                        ->label(__('admin.fields.body'))
                        ->required()
                        ->fileAttachmentsDisk('s3')
                        ->fileAttachmentsDirectory('send-mail/attachments')
                        ->fileAttachmentsVisibility('private')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
