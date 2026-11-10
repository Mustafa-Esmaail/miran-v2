<?php

namespace App\Filament\Admin\Resources\Trainers;

use App\Filament\Admin\Resources\Trainers\Schemas\TrainerForm;
use App\Filament\Admin\Resources\Trainers\Tables\TrainersTable;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrainerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.trainers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.trainer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.trainers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_people_group');
    }

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        if (! $user) {
            return false;
        }

        return $user->can('trainers.viewAny') || $user->can('users.viewAny');
    }

    public static function form(Schema $schema): Schema
    {
        return TrainerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainers::route('/'),
            'create' => Pages\CreateTrainer::route('/create'),
            'view' => Pages\ViewTrainer::route('/{record}'),
            'edit' => Pages\EditTrainer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'instructor'))
            ->withCount('teachingCourses');

        $user = Filament::auth()->user();

        if ($user->hasRole('instructor')) {
            $query->whereKey($user->id);
        }

        return $query;
    }
}
