<?php

namespace App\Filament\Admin\Resources\CertificateRequests\Schemas;

use App\Enums\CertificateRequestStatus;
use App\Models\CertificateRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateRequestForm
{
    public static function configure(Schema $schema, bool $statusEditable): Schema
    {
        $locale = app()->getLocale();

        $status = Select::make('status')
            ->label(__('admin.labels.status'))
            ->options([
                CertificateRequestStatus::Pending->value => __('admin.options.pending'),
                CertificateRequestStatus::Approved->value => __('admin.options.approved'),
                CertificateRequestStatus::Rejected->value => __('admin.options.rejected'),
                CertificateRequestStatus::SentToManarCenter->value => __('admin.options.sent_to_manar_center'),
            ]);

        if (! $statusEditable) {
            $status = $status->disabled()->dehydrated(false);
        }

        return $schema->schema([
            Section::make(__('admin.labels.trainee'))
                ->schema([
                    TextInput::make('user.name')
                        ->label(__('admin.labels.trainee'))
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('user.email')
                        ->label(__('admin.labels.email'))
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('courseGroup_label')
                        ->label(__('admin.labels.group'))
                        ->disabled()
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($component, CertificateRequest $record) use ($locale): void {
                            $component->state(
                                $record->courseGroup?->getTranslation('title', $locale) ?? '-'
                            );
                        }),
                ])
                ->columns(3),

            Section::make('الاسم بالعربية')
                ->schema([
                    TextInput::make('first_name_ar')->label('الاسم الاول')->disabled()->dehydrated(false),
                    TextInput::make('second_name_ar')->label('الاسم الثاني')->disabled()->dehydrated(false),
                    TextInput::make('third_name_ar')->label('الاسم الثالث')->disabled()->dehydrated(false),
                    TextInput::make('last_name_ar')->label('الاسم الاخير')->disabled()->dehydrated(false),
                ])
                ->columns(4),

            Section::make('الاسم بالإنجليزية')
                ->schema([
                    TextInput::make('first_name_en')->label('الاسم الاول باللغة الإنجليزية')->disabled()->dehydrated(false),
                    TextInput::make('second_name_en')->label('الاسم الثاني باللغة الإنجليزية')->disabled()->dehydrated(false),
                    TextInput::make('third_name_en')->label('الاسم الثالث باللغة الإنجليزية')->disabled()->dehydrated(false),
                    TextInput::make('last_name_en')->label('الاسم الاخير باللغة الإنجليزية')->disabled()->dehydrated(false),
                ])
                ->columns(4),

            Section::make('البيانات الشخصية')
                ->schema([
                    TextInput::make('id_number')->label('رقم الهوية')->disabled()->dehydrated(false),
                    DatePicker::make('birth_date')->label('تاريخ الميلاد')->disabled()->dehydrated(false),
                    Select::make('gender')
                        ->label('النوع')
                        ->options(['male' => 'ذكر', 'female' => 'انثى'])
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('nationality')->label('الجنسية')->disabled()->dehydrated(false),
                    TextInput::make('qualification')->label('المؤهل')->disabled()->dehydrated(false),
                    TextInput::make('email')->label('البريد الالكتروني')->disabled()->dehydrated(false),
                    TextInput::make('phone')->label('رقم الجوال')->disabled()->dehydrated(false),
                    $status,
                ])
                ->columns(3),
        ]);
    }
}
