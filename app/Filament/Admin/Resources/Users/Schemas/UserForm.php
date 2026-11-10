<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.profile'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->label(__('admin.fields.name'))->required()->maxLength(255),
                    TextInput::make('email')
                        ->label(__('admin.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => __('admin.validation.email_unique'),
                        ]),
                    TextInput::make('phone')->label(__('admin.fields.phone'))->tel()->maxLength(30),
                    TextInput::make('national_id')->label(__('admin.fields.national_id'))->maxLength(50),
                    TextInput::make('nationality')->label(__('admin.fields.nationality'))->maxLength(100),
                    DatePicker::make('birth_date')->label(__('admin.fields.birth_date'))->nullable(),
                    Select::make('gender')->label(__('admin.fields.gender'))->options([
                        'male' => __('admin.options.male'),
                        'female' => __('admin.options.female'),
                    ])->nullable(),
                    Textarea::make('about')->label(__('admin.fields.about'))->rows(4)->columnSpanFull(),
                    FileUpload::make('avatar_path')
                        ->label(__('admin.fields.avatar'))
                        ->image()
                        ->disk('s3')
                        ->directory('avatars')
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
                ->columnSpanFull()
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
                            User::STATUS_SUSPENDED => __('admin.options.suspended'),
                            User::STATUS_DELETED => __('admin.options.deleted'),
                        ])
                        ->default(User::STATUS_ACTIVE)
                        ->required(),
                    Select::make('roles')
                        ->relationship(
                            'roles',
                            'name',
                            modifyQueryUsing: fn (Builder $query) => $query->where('guard_name', 'admin')
                        )
                        ->multiple()
                        ->preload()
                        ->searchable(),
                    Toggle::make('is_chat_banned')
                        ->label(__('admin.fields.chat_banned'))
                        ->helperText(__('admin.helpers.chat_banned'))
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
