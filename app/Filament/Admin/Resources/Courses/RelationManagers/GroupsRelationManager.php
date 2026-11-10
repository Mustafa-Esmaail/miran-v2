<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Enums\CourseGroupStatus;
use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use App\Filament\Forms\TranslatableField;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_groups');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.group'))
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TextInput::make('group_price_amount')->numeric()->label(__('admin.labels.price'))->prefix('SAR'),
                    DateTimePicker::make('start_at')
                        ->seconds(false),
                    DateTimePicker::make('end_at')
                        ->seconds(false),
                    TextInput::make('capacity')->numeric(),
                    DateTimePicker::make('enrollment_deadline_at')
                        ->seconds(false),
                    Select::make('status')->options([
                        'open' => __('admin.options.open'),
                        'closed' => __('admin.options.closed'),
                        'archived' => __('admin.options.archived'),
                    ])->required(),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.group'))
                ->formatStateUsing(fn ($state, $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof CourseGroupStatus ? $state->value : (string) $state;

                    return match ($value) {
                        CourseGroupStatus::Open->value => 'success',
                        CourseGroupStatus::Closed->value => 'warning',
                        CourseGroupStatus::Archived->value => 'danger',
                        default => 'secondary',
                    };
                }),
            TextColumn::make('start_at')->dateTime('Y-m-d H:i'),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->actions([
                EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => CourseGroupResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => CourseGroupResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.course_groups_relation.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_groups_relation.description'));
    }
}
