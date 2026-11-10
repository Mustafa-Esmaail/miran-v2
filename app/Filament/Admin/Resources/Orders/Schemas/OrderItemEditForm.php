<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Support\Purchasables\PurchasableResolver;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderItemEditForm
{
    private const BUNDLE_COURSE_GROUPS_KEY = 'bundleCourseGroupsSection';

    public static function configure(Schema $schema, bool $includeQuantity = false): Schema
    {
        return $schema->schema([
            Select::make('purchasable_type')
                ->label(__('admin.labels.item_type'))
                ->options([
                    CourseGroup::class => __('admin.options.course_group'),
                    Bundle::class => __('admin.options.bundle'),
                ])
                ->live()
                ->afterStateUpdated(function (Set $set, Select $component): void {
                    $set('purchasable_id', null);
                    $set('bundle_course_groups', []);

                    $component->getContainer()->getComponent(self::BUNDLE_COURSE_GROUPS_KEY)?->getChildSchema()?->fill();
                })
                ->required(),
            Select::make('purchasable_id')
                ->label(__('admin.labels.item'))
                ->options(function (Get $get) {
                    $type = $get('purchasable_type');

                    if ($type === CourseGroup::class) {
                        return CourseGroup::query()
                            ->with('course')
                            ->get()
                            ->mapWithKeys(function (CourseGroup $group) {
                                $courseTitle = $group->course?->getTranslation('title', app()->getLocale()) ?? 'Course';
                                $groupTitle = $group->getTranslation('title', app()->getLocale());

                                return [$group->id => $courseTitle.' - '.$groupTitle];
                            });
                    }

                    if ($type === Bundle::class) {
                        return Bundle::query()
                            ->get()
                            ->mapWithKeys(fn (Bundle $bundle) => [
                                $bundle->id => $bundle->getTranslation('title', app()->getLocale()) ?? '',
                            ]);
                    }

                    return [];
                })
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state, Get $get, Select $component): void {
                    $set('bundle_course_groups', []);

                    if ($state) {
                        $type = $get('purchasable_type');
                        $resolver = app(PurchasableResolver::class);

                        if ($type === CourseGroup::class) {
                            $group = CourseGroup::query()->with('course.discounts')->find($state);
                            if ($group) {
                                $set('price_amount', $resolver->getPriceAmount($group));
                            }
                        }

                        if ($type === Bundle::class) {
                            $bundle = Bundle::query()->with(['discounts', 'courses'])->find($state);
                            if ($bundle) {
                                $set('price_amount', $resolver->getPriceAmount($bundle));
                            }
                        }
                    }

                    $component->getContainer()->getComponent(self::BUNDLE_COURSE_GROUPS_KEY)?->getChildSchema()?->fill();
                }),
            Section::make(__('admin.labels.course_group_selection'))
                ->key(self::BUNDLE_COURSE_GROUPS_KEY)
                ->description(__('admin.helpers.select_group_per_course'))
                ->schema(function (Get $get): array {
                    if ($get('purchasable_type') !== Bundle::class || ! $get('purchasable_id')) {
                        return [];
                    }

                    $bundle = Bundle::query()->with('courses')->find($get('purchasable_id'));

                    if (! $bundle) {
                        return [];
                    }

                    return $bundle->courses
                        ->map(fn ($course) => Select::make("bundle_course_groups.{$course->id}")
                            ->label($course->getTranslation('title', app()->getLocale()) ?? __('admin.labels.course_group'))
                            ->options(
                                CourseGroup::query()
                                    ->where('course_id', $course->id)
                                    ->get()
                                    ->mapWithKeys(fn (CourseGroup $group): array => [$group->id => $group->getTranslation('title', app()->getLocale())])
                            )
                            ->searchable()
                            ->required())
                        ->values()
                        ->all();
                })
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (Get $get): bool => $get('purchasable_type') === Bundle::class),
            TextInput::make('price_amount')
                ->label(__('admin.labels.unit_price'))
                ->numeric()
                ->minValue(0)
                ->prefix('SAR')
                ->helperText(__('admin.helpers.auto_filled_from_group_price'))
                ->required(),
            TextInput::make('quantity')
                ->label(__('admin.labels.quantity'))
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required()
                ->visible($includeQuantity),
            Textarea::make('change_reason')
                ->label(__('admin.labels.change_reason'))
                ->required()
                ->columnSpanFull(),
        ])->columns(2);
    }
}
