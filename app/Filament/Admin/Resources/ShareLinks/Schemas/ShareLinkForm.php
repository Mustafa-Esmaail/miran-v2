<?php

namespace App\Filament\Admin\Resources\ShareLinks\Schemas;

use App\Filament\Admin\Resources\ShareLinks\ShareLinkResource;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\Employee;
use App\Support\CrmScope;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ShareLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.share_link'))
                ->columnSpanFull()
                ->schema([
                    Select::make('employee_id')
                        ->label(__('admin.models.employee'))
                        ->options(fn (): array => ShareLinkResource::employeeScope()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Employee $e): array => [
                                $e->id => trim("{$e->name} ({$e->employee_code})"),
                            ])
                            ->all())
                        ->default(fn (): ?int => CrmScope::viewer()?->id)
                        ->disabled(fn (): bool => CrmScope::isLimitedToSelf())
                        ->dehydrated()
                        ->searchable()
                        ->required(),

                    Select::make('target_type')
                        ->label(__('admin.labels.target_type'))
                        ->options(static::targetTypeOptions())
                        ->default('homepage')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('target_id', null)),

                    Select::make('target_id')
                        ->label(__('admin.labels.target'))
                        ->options(fn (Get $get): array => static::targetOptions($get('target_type')))
                        ->searchable()
                        ->visible(fn (Get $get): bool => static::hasSpecificTarget($get('target_type')))
                        ->required(fn (Get $get): bool => static::hasSpecificTarget($get('target_type')))
                        ->live(),
                ])
                ->columns(2),
        ]);
    }

    /**
     * Target types a share link can point to.
     *
     * @return array<string, string>
     */
    public static function targetTypeOptions(): array
    {
        return [
            'homepage' => __('admin.options.homepage'),
            'course' => __('admin.options.course'),
            'bundle' => __('admin.options.bundle'),
            'category' => __('admin.options.category'),
        ];
    }

    /**
     * Whether the target type needs a specific record (course, bundle or category).
     */
    public static function hasSpecificTarget(?string $targetType): bool
    {
        return in_array($targetType, ['course', 'bundle', 'category'], true);
    }

    /**
     * Selectable records for a target type, labelled in the current locale.
     *
     * @return array<int, string>
     */
    public static function targetOptions(?string $targetType): array
    {
        $locale = app()->getLocale();

        return match ($targetType) {
            'course' => Course::query()
                ->orderBy('slug')
                ->get()
                ->mapWithKeys(fn (Course $c): array => [
                    $c->id => $c->getTranslation('title', $locale) ?: $c->slug,
                ])
                ->all(),
            'bundle' => Bundle::query()
                ->where('is_active', true)
                ->orderBy('slug')
                ->get()
                ->mapWithKeys(fn (Bundle $b): array => [
                    $b->id => $b->getTranslation('title', $locale) ?: $b->slug,
                ])
                ->all(),
            'category' => Category::query()
                ->where('is_active', true)
                ->orderBy('slug')
                ->get()
                ->mapWithKeys(fn (Category $category): array => [
                    $category->id => $category->getTranslation('name', $locale) ?: $category->slug,
                ])
                ->all(),
            default => [],
        };
    }
}
