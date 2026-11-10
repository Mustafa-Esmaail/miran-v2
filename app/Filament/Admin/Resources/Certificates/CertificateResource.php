<?php

namespace App\Filament\Admin\Resources\Certificates;

use App\Filament\Admin\Resources\Certificates\Schemas\CertificateForm;
use App\Filament\Admin\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trophy';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.certificates');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.certificate');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.certificates');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_certificates_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CertificateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificates::route('/'),
            'view' => Pages\ViewCertificate::route('/{record}'),
            'edit' => Pages\EditCertificate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeCourseRelation($query, $user, 'course', 'certificates.viewAny');

        return $query->with(['user', 'course', 'courseGroup']);
    }
}
