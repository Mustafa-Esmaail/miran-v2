<?php

namespace App\Filament\Admin\Resources\CertificateRequests\Tables;

use App\Enums\CertificateRequestStatus;
use App\Filament\Admin\Resources\CertificateRequests\CertificateRequestResource;
use App\Models\CertificateRequest;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificateRequestsTable
{
    public static function configure(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->query(
                Enrollment::query()
                    ->leftJoin('certificate_requests', function ($join): void {
                        $join->on('certificate_requests.user_id', '=', 'enrollments.user_id')
                            ->on('certificate_requests.course_group_id', '=', 'enrollments.course_group_id');
                    })
                    ->select('enrollments.*')
                    ->addSelect([
                        'certificate_requests.id as certificate_request_id',
                        'certificate_requests.status as certificate_status',
                        'certificate_requests.id_number as cr_id_number',
                        'certificate_requests.first_name_ar as cr_first_name_ar',
                        'certificate_requests.second_name_ar as cr_second_name_ar',
                        'certificate_requests.third_name_ar as cr_third_name_ar',
                        'certificate_requests.last_name_ar as cr_last_name_ar',
                        'certificate_requests.first_name_en as cr_first_name_en',
                        'certificate_requests.second_name_en as cr_second_name_en',
                        'certificate_requests.third_name_en as cr_third_name_en',
                        'certificate_requests.last_name_en as cr_last_name_en',
                        'certificate_requests.birth_date as cr_birth_date',
                        'certificate_requests.gender as cr_gender',
                        'certificate_requests.nationality as cr_nationality',
                        'certificate_requests.qualification as cr_qualification',
                        'certificate_requests.email as cr_email',
                        'certificate_requests.phone as cr_phone',
                    ])
                    ->with(['user', 'course', 'courseGroup'])
            )
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('user.name')->label(__('admin.labels.trainee'))->searchable(),
                TextColumn::make('course.slug')
                    ->label(__('admin.labels.course'))
                    ->formatStateUsing(fn (mixed $_, Enrollment $record) => $record->course?->getTranslation('title', $locale) ?? '-')
                    ->toggleable(),
                TextColumn::make('courseGroup.title')
                    ->label(__('admin.labels.group'))
                    ->formatStateUsing(fn (mixed $_, Enrollment $record) => $record->courseGroup?->getTranslation('title', $locale) ?? '-')
                    ->toggleable(),
                TextColumn::make('cr_id_number')->label('رقم الهوية')->toggleable()->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('certificate_requests.id_number', 'like', "%{$search}%")),
                TextColumn::make('cr_first_name_ar')->label('الاسم الاول')->toggleable()->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('certificate_requests.first_name_ar', 'like', "%{$search}%")),
                TextColumn::make('cr_last_name_ar')->label('الاسم الاخير')->toggleable()->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('certificate_requests.last_name_ar', 'like', "%{$search}%")),
                TextColumn::make('cr_first_name_en')->label('الاسم الاول بالإنجليزية')->toggleable()->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('certificate_requests.first_name_en', 'like', "%{$search}%")),
                TextColumn::make('cr_last_name_en')->label('الاسم الاخير بالإنجليزية')->toggleable()->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('certificate_requests.last_name_en', 'like', "%{$search}%")),
                TextColumn::make('cr_gender')->label('النوع')->toggleable()->placeholder('-'),
                TextColumn::make('cr_nationality')->label('الجنسية')->toggleable()->placeholder('-'),
                TextColumn::make('cr_email')->label('البريد الالكتروني')->toggleable()->placeholder('-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('certificate_requests.email', 'like', "%{$search}%")),
                TextColumn::make('cr_phone')->label('رقم الجوال')->toggleable()->placeholder('-'),
                TextColumn::make('certificate_status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->formatStateUsing(function (mixed $state, Enrollment $record): string {
                        if (! $record->certificate_request_id) {
                            return __('admin.options.not_applied');
                        }

                        return match (CertificateRequestStatus::tryFrom((string) $state)) {
                            CertificateRequestStatus::Pending => __('admin.options.pending'),
                            CertificateRequestStatus::Approved => __('admin.options.approved'),
                            CertificateRequestStatus::Rejected => __('admin.options.rejected'),
                            CertificateRequestStatus::SentToManarCenter => __('admin.options.sent_to_manar_center'),
                            default => __('admin.options.not_applied'),
                        };
                    })
                    ->color(function (mixed $state, Enrollment $record): string {
                        if (! $record->certificate_request_id) {
                            return 'gray';
                        }

                        return match (CertificateRequestStatus::tryFrom((string) $state)) {
                            CertificateRequestStatus::Approved => 'success',
                            CertificateRequestStatus::SentToManarCenter => 'info',
                            CertificateRequestStatus::Rejected => 'danger',
                            default => 'warning',
                        };
                    })
                    ->sortable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])->filters([
                Filter::make('course_group')
                    ->form([
                        Select::make('course_id')
                            ->label(__('admin.labels.course'))
                            ->options(
                                fn (): array => Course::query()
                                    ->get()
                                    ->mapWithKeys(fn (Course $c): array => [
                                        $c->id => $c->getTranslation('title', app()->getLocale()),
                                    ])
                                    ->all()
                            )
                            ->searchable()
                            ->live(),
                        Select::make('course_group_id')
                            ->label(__('admin.labels.group'))
                            ->options(function (Get $get): array {
                                $courseId = $get('course_id');

                                if (! $courseId) {
                                    return [];
                                }

                                return CourseGroup::query()
                                    ->where('course_id', $courseId)
                                    ->get()
                                    ->mapWithKeys(fn (CourseGroup $g): array => [
                                        $g->id => $g->getTranslation('title', app()->getLocale()),
                                    ])
                                    ->all();
                            })
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['course_id'] ?? null, fn (Builder $q, $courseId) => $q->where('course_id', $courseId))
                            ->when($data['course_group_id'] ?? null, fn (Builder $q, $groupId) => $q->where('enrollments.course_group_id', $groupId));
                    }),
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label(__('admin.labels.from')),
                        DatePicker::make('created_until')->label(__('admin.labels.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('enrollments.created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $q, string $date) => $q->whereDate('enrollments.created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = __('admin.labels.from').': '.$data['created_from'];
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = __('admin.labels.until').': '.$data['created_until'];
                        }

                        return $indicators;
                    }),
                SelectFilter::make('certificate_status')
                    ->label(__('admin.labels.status'))
                    ->options([
                        CertificateRequestStatus::Pending->value => __('admin.options.pending'),
                        CertificateRequestStatus::Approved->value => __('admin.options.approved'),
                        CertificateRequestStatus::Rejected->value => __('admin.options.rejected'),
                        CertificateRequestStatus::SentToManarCenter->value => __('admin.options.sent_to_manar_center'),
                    ])
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $status) => $q->where('certificate_requests.status', $status)
                    )),
                Filter::make('request_state')
                    ->form([
                        Select::make('value')
                            ->label(__('admin.labels.request_state'))
                            ->options([
                                'submitted' => __('admin.options.request_submitted'),
                                'not_submitted' => __('admin.options.request_not_submitted'),
                            ])
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'submitted' => $query->whereNotNull('certificate_requests.id'),
                            'not_submitted' => $query->whereNull('certificate_requests.id'),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): array {
                        return match ($data['value'] ?? null) {
                            'submitted' => [__('admin.options.request_submitted')],
                            'not_submitted' => [__('admin.options.request_not_submitted')],
                            default => [],
                        };
                    }),
            ])->actions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->visible(fn (Enrollment $record): bool => filled($record->certificate_request_id))
                    ->url(fn (Enrollment $record): ?string => $record->certificate_request_id
                        ? CertificateRequestResource::getUrl('view', ['record' => $record->certificate_request_id])
                        : null),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->visible(fn (Enrollment $record): bool => filled($record->certificate_request_id))
                    ->url(fn (Enrollment $record): ?string => $record->certificate_request_id
                        ? CertificateRequestResource::getUrl('edit', ['record' => $record->certificate_request_id])
                        : null),
                Action::make('mark_sent')
                    ->label(__('admin.actions.mark_sent_to_manar_center'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Enrollment $record): bool => filled($record->certificate_request_id)
                        && $record->certificate_status !== CertificateRequestStatus::SentToManarCenter->value)
                    ->requiresConfirmation()
                    ->action(fn (Enrollment $record) => CertificateRequest::whereKey($record->certificate_request_id)
                        ->update(['status' => CertificateRequestStatus::SentToManarCenter])),
            ])->headerActions([
                Action::make('export')
                    ->label(__('admin.actions.export'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn (HasTable $livewire) => CertificateRequestResource::exportToXlsx($livewire->getFilteredTableQuery())),
            ])
            ->defaultSort('enrollments.created_at', 'desc');
    }
}
