<?php

namespace App\Filament\Admin\Resources\Coupons\Schemas;

use App\Enums\CouponAppliesTo;
use App\Filament\Forms\TranslatableField;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\Employee;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.basics'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TextInput::make('code')
                        ->required()
                        ->helperText(__('admin.helpers.customers_enter_this_at_checkout')),
                    Select::make('type')->options([
                        'percent' => __('admin.options.percent'),
                        'fixed' => __('admin.options.fixed'),
                    ])->required(),
                    TextInput::make('value')->numeric()->required(),
                    TextInput::make('min_order_amount')->numeric()->label(__('admin.labels.minimum_order')),
                    TextInput::make('min_items_count')->numeric()->label(__('admin.labels.minimum_items')),
                    Select::make('employee_id')
                        ->label(__('admin.labels.sales_person'))
                        ->options(fn () => Employee::query()
                            ->whereNotNull('user_id')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->helperText(__('admin.helpers.coupon_sales_person')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.limits'))
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('starts_at')
                        ->seconds(false),
                    DateTimePicker::make('ends_at')
                        ->seconds(false),
                    TextInput::make('usage_limit')->numeric(),
                    TextInput::make('per_user_limit')->numeric(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.applicability'))
                ->columnSpanFull()
                ->schema([
                    Select::make('applies_to')
                        ->label(__('admin.labels.applies_to'))
                        ->options([
                            CouponAppliesTo::Course->value => __('admin.options.course'),
                            CouponAppliesTo::Bundle->value => __('admin.options.bundle'),
                            CouponAppliesTo::Both->value => __('admin.options.both'),
                        ])
                        ->default(CouponAppliesTo::Both->value)
                        ->required()
                        ->live()
                        ->helperText(__('admin.helpers.coupon_applies_to')),
                    Select::make('category_ids')
                        ->label(__('admin.labels.categories'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn () => Category::query()->get()
                            ->mapWithKeys(fn (Category $category) => [$category->id => $category->getTranslation('name', app()->getLocale())]))
                        ->helperText(__('admin.helpers.leave_targets_empty')),
                    Select::make('course_ids')
                        ->label(__('admin.labels.courses'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => in_array($get('applies_to'), [CouponAppliesTo::Course->value, CouponAppliesTo::Both->value], true))
                        ->options(fn () => Course::query()->get()
                            ->mapWithKeys(fn (Course $course) => [$course->id => $course->getTranslation('title', app()->getLocale())])),
                    Select::make('bundle_ids')
                        ->label(__('admin.labels.bundles'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => in_array($get('applies_to'), [CouponAppliesTo::Bundle->value, CouponAppliesTo::Both->value], true))
                        ->options(fn () => Bundle::query()->get()
                            ->mapWithKeys(fn (Bundle $bundle) => [$bundle->id => $bundle->getTranslation('title', app()->getLocale())])),
                ])
                ->columns(1),
        ]);
    }
}
