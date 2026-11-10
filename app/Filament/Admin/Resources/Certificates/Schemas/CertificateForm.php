<?php

namespace App\Filament\Admin\Resources\Certificates\Schemas;

use App\Enums\CertificateStatus;
use App\Models\CourseGroup;
use App\Models\User;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.certificate'))
                ->columnSpanFull()
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->email ?? $record->name ?? $record->phone ?? (string) $record->id)
                        ->label(__('admin.labels.trainee'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('course_id')
                        ->relationship(
                            'course',
                            'slug',
                            modifyQueryUsing: fn (Builder $query) => app(CourseAccessService::class)->scopeCourses($query, Filament::auth()->user(), 'courses.viewAny')
                        )
                        ->label(__('admin.labels.course'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                    Select::make('course_group_id')
                        ->options(function (Get $get): array {
                            $courseId = $get('course_id');

                            if (! $courseId) {
                                return [];
                            }

                            $query = CourseGroup::query()->where('course_id', $courseId);
                            $query = app(CourseAccessService::class)->scopeCourseRelation($query, Filament::auth()->user(), 'course', 'course_groups.viewAny');

                            return $query->get()
                                ->mapWithKeys(fn (CourseGroup $group): array => [$group->id => $group->getTranslation('title', app()->getLocale())])
                                ->all();
                        })
                        ->label(__('admin.labels.group'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->options([
                            CertificateStatus::Pending->value => __('admin.options.pending'),
                            CertificateStatus::Approved->value => __('admin.options.approved'),
                            CertificateStatus::Rejected->value => __('admin.options.rejected'),
                        ])
                        ->required(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.approval'))
                ->columnSpanFull()
                ->schema([
                    Select::make('approved_by')
                        ->relationship('approver', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->email ?? $record->name ?? $record->phone ?? (string) $record->id)
                        ->label(__('admin.labels.approved_by'))
                        ->searchable()
                        ->preload(),
                    DateTimePicker::make('approved_at')
                        ->seconds(false)
                        ->label(__('admin.labels.approved_at')),
                    FileUpload::make('file_path')
                        ->label(__('admin.labels.certificate_file'))
                        ->disk('s3')
                        ->directory('certificates')
                        ->visibility('private'),
                ])
                ->columns(2),
        ]);
    }
}
