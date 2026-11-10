<?php

namespace App\Filament\Admin\Resources\Courses\Schemas;

use App\Enums\CourseTemplate;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Admin fields for the marketing content shown on the bootcamp course page.
 * Everything is stored in `courses.template_content` with `{key}.ar` / `{key}.en` pairs.
 */
class CourseBootcampContentForm
{
    public static function section(): Section
    {
        return Section::make(__('admin.bootcamp.sections.page_content'))
            ->description(__('admin.bootcamp.helpers.page_content'))
            ->visible(fn (Get $get): bool => self::isBootcamp($get('template')))
            ->collapsible()
            ->columnSpanFull()
            ->schema([
                Section::make(__('admin.bootcamp.sections.hero'))
                    ->schema([
                        ...self::pair('template_content.tagline', 'tagline'),
                        ...self::pair('template_content.title_highlight', 'title_highlight'),
                        FileUpload::make('template_content.hero_image')
                            ->label(__('admin.bootcamp.fields.hero_image'))
                            ->helperText(__('admin.bootcamp.helpers.hero_image'))
                            ->disk('s3')
                            ->directory('course-bootcamp')
                            ->visibility('private')
                            ->image()
                            ->columnSpanFull(),
                        self::list('template_content.industries', 'industries', ['text']),
                    ])
                    ->hidden(fn (Get $get): bool => self::isTemplate($get('template'), CourseTemplate::BootcampV2))
                    ->columns(2),
                Section::make(__('admin.bootcamp.sections.pillars'))
                    ->schema([
                        self::list('template_content.pillars', 'pillars', ['title', 'description', 'tag', 'footer']),
                    ])
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.method'))
                    ->schema([
                        self::list('template_content.method_steps', 'method_steps', ['title', 'description']),
                        ...self::pair('template_content.method_note', 'method_note', textarea: true),
                    ])
                    ->columns(2)
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.projects'))
                    ->schema([
                        self::list('template_content.projects', 'projects', ['title', 'description', 'tag', 'module_label'], withImage: true),
                    ])
                    ->hidden(fn (Get $get): bool => self::isTemplate($get('template'), CourseTemplate::BootcampV2))
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.transformation'))
                    ->schema([
                        self::list('template_content.before_items', 'before_items', ['text']),
                        self::list('template_content.after_items', 'after_items', ['text']),
                    ])
                    ->hidden(fn (Get $get): bool => self::isTemplate($get('template'), CourseTemplate::BootcampV2))
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.audience'))
                    ->schema([
                        self::list('template_content.audience', 'audience', ['icon', 'title', 'description']),
                        ...self::pair('template_content.not_for', 'not_for', textarea: true),
                    ])
                    ->columns(2)
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.pricing'))
                    ->schema([
                        ...self::pair('template_content.offer_label', 'offer_label'),
                        self::list('template_content.included_features', 'included_features', ['text']),
                    ])
                    ->hidden(fn (Get $get): bool => self::isTemplate($get('template'), CourseTemplate::BootcampV2))
                    ->columns(2)
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.faq'))
                    ->schema([
                        self::list('template_content.faqs', 'faqs', ['question', 'answer']),
                    ])
                    ->collapsed(),
                Section::make(__('admin.bootcamp.sections.final_cta'))
                    ->schema([
                        ...self::pair('template_content.final_cta_title', 'final_cta_title'),
                        ...self::pair('template_content.final_cta_text', 'final_cta_text', textarea: true),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function isBootcamp(mixed $template): bool
    {
        return self::resolveTemplate($template)?->isBootcamp() ?? false;
    }

    /**
     * Groups only one bootcamp design uses are hidden for the other.
     */
    private static function isTemplate(mixed $template, CourseTemplate $expected): bool
    {
        return self::resolveTemplate($template) === $expected;
    }

    private static function resolveTemplate(mixed $template): ?CourseTemplate
    {
        return $template instanceof CourseTemplate ? $template : CourseTemplate::tryFrom((string) $template);
    }

    /**
     * @return array<int, TextInput|Textarea>
     */
    private static function pair(string $path, string $field, bool $textarea = false): array
    {
        $label = __('admin.bootcamp.fields.'.$field);
        $component = $textarea ? Textarea::class : TextInput::class;

        return [
            $component::make($path.'.ar')->label($label.' (AR)'),
            $component::make($path.'.en')->label($label.' (EN)'),
        ];
    }

    /**
     * @param  array<int, string>  $fields
     */
    private static function list(string $path, string $name, array $fields, bool $withImage = false): Repeater
    {
        $schema = collect($fields)
            ->flatMap(fn (string $field): array => self::pair($field, $field, textarea: in_array($field, ['description', 'answer'], true)))
            ->all();

        if ($withImage) {
            $schema[] = FileUpload::make('image')
                ->label(__('admin.bootcamp.fields.image'))
                ->disk('s3')
                ->directory('course-bootcamp')
                ->visibility('private')
                ->image()
                ->columnSpanFull();
        }

        return Repeater::make($path)
            ->label(__('admin.bootcamp.fields.'.$name))
            ->schema($schema)
            ->columns(2)
            ->reorderable()
            ->collapsible()
            ->defaultItems(0)
            ->columnSpanFull();
    }
}
