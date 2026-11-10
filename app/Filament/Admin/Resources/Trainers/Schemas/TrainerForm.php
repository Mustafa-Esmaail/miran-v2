<?php

namespace App\Filament\Admin\Resources\Trainers\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class TrainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.profile'))
                ->schema([
                    TextInput::make('name')->label(__('admin.fields.name'))->required()->maxLength(255),
                    TextInput::make('email')
                        ->label(__('admin.fields.email'))
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => __('admin.validation.email_unique'),
                        ]),
                    TextInput::make('phone')->label(__('admin.fields.phone'))->tel()->maxLength(30),
                    Textarea::make('about')->label(__('admin.fields.about'))->rows(4)->columnSpanFull(),
                    FileUpload::make('avatar_path')
                        ->label(__('admin.fields.avatar'))
                        ->image()
                        ->disk('s3')
                        ->directory('avatars')
                        ->visibility('private')
                        ->columnSpanFull(),
                    FileUpload::make('cv_image_path')
                        ->label(__('admin.fields.cv_image'))
                        ->image()
                        ->disk('s3')
                        ->directory('cvs')
                        ->visibility('private')
                        ->columnSpanFull(),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->helperText(__('admin.helpers.leave_blank_to_keep_the_current_password'))
                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $context): bool => $context === 'create'),
                ])
                ->columns(2),
            Section::make(__('admin.sections.access'))
                ->schema([
                    Select::make('locale')
                        ->label(__('admin.fields.locale'))
                        ->options([
                            'en' => __('admin.options.english'),
                            'ar' => __('admin.options.arabic'),
                        ])
                        ->nullable(),
                    Select::make('status')
                        ->label(__('admin.fields.status'))
                        ->options([
                            User::STATUS_ACTIVE => __('admin.options.active'),
                            User::STATUS_DELETED => __('admin.options.deleted'),
                        ])
                        ->default(User::STATUS_ACTIVE)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }
}
