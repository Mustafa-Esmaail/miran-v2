<?php

namespace App\Filament\Admin\Resources\CallEvaluations;

use App\Filament\Admin\Resources\CallEvaluations\Pages\CreateCallEvaluation;
use App\Filament\Admin\Resources\CallEvaluations\Pages\EditCallEvaluation;
use App\Filament\Admin\Resources\CallEvaluations\Pages\ListCallEvaluations;
use App\Filament\Admin\Resources\CallEvaluations\Pages\ViewCallEvaluation;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationForm;
use App\Filament\Admin\Resources\CallEvaluations\Schemas\CallEvaluationInfolist;
use App\Filament\Admin\Resources\CallEvaluations\Tables\CallEvaluationsTable;
use App\Models\CallEvaluation;
use App\Services\CallQualityService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CallEvaluationResource extends Resource
{
    protected static ?string $model = CallEvaluation::class;

    protected static ?string $slug = 'call-evaluations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.call_quality.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.call_quality.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.call_quality.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_quality_group');
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record === null ? null : '#'.$record->getKey();
    }

    public static function form(Schema $schema): Schema
    {
        return CallEvaluationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CallEvaluationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallEvaluationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallEvaluations::route('/'),
            'create' => CreateCallEvaluation::route('/create'),
            'view' => ViewCallEvaluation::route('/{record}'),
            'edit' => EditCallEvaluation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'employee.team',
            'evaluator',
            'serviceRequest',
            'crmActivity.employee',
            'discussedBy',
            'approvedBy',
        ]);

        return app(CallQualityService::class)->scopeEvaluations($query, auth('admin')->user());
    }
}
