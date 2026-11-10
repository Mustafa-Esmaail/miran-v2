<?php

namespace App\Filament\Admin\Resources\Companies\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('admin.labels.name'))
                ->required()
                ->maxLength(190)
                ->columnSpanFull(),

            TextInput::make('industry')
                ->label(__('admin.labels.industry'))
                ->maxLength(100),

            Select::make('size')
                ->label(__('admin.labels.company_size'))
                ->options([
                    'micro' => __('admin.options.micro'),
                    'small' => __('admin.options.small'),
                    'medium' => __('admin.options.medium'),
                    'large' => __('admin.options.large'),
                    'enterprise' => __('admin.options.enterprise'),
                ])
                ->nullable(),

            TextInput::make('website')
                ->label(__('admin.labels.website'))
                ->url()
                ->maxLength(255),

            TextInput::make('tax_id')
                ->label(__('admin.labels.tax_id'))
                ->maxLength(50),

            Select::make('account_manager_id')
                ->label(__('admin.labels.account_manager'))
                ->options(fn () => Employee::query()
                    ->whereNotNull('user_id')
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),

            Textarea::make('address')
                ->label(__('admin.labels.address'))
                ->rows(2)
                ->columnSpanFull(),

            Textarea::make('notes')
                ->label(__('admin.labels.notes'))
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }
}
