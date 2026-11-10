<?php

namespace App\Filament\Admin\Resources\CourseDiscounts\Schemas;

use App\Models\Discount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseDiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.eligibility'))
                ->columnSpanFull()
                ->schema([
                    Select::make('courses')
                        ->multiple()
                        ->relationship('courses', 'slug')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                        ->searchable(['title->en', 'title->ar', 'slug'])
                        ->preload()
                        ->required()
                        ->helperText(__('admin.helpers.select_which_courses_this_discount_applies_to')),
                ]),
            Section::make(__('admin.sections.rules'))
                ->columnSpanFull()
                ->schema([
                    Select::make('type')
                        ->options([
                            Discount::TYPE_FIXED => __('admin.options.fixed'),
                            Discount::TYPE_PERCENTAGE => __('admin.options.percentage'),
                        ])
                        ->required(),
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->helperText(__('admin.helpers.use_percentage_for_discounts_or_fixed_for_sar_value')),
                    TextInput::make('max_amount')->numeric()->label(__('admin.labels.discount_limit')),
                    Toggle::make('is_active')->label(__('admin.labels.active'))->default(true),
                ])
                ->columns(2),
        ]);
    }
}
