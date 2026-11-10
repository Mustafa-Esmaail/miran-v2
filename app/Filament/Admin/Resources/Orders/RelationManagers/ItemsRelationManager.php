<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\Bundles\BundleResource;
use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Orders\Schemas\OrderItemEditForm;
use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\OrderActivityLog;
use App\Models\OrderItem;
use App\Services\OrderItemChangeService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_items');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('type_label')
                ->label(__('admin.labels.type'))
                ->state(fn (OrderItem $record) => $record->metadata_snapshot['type_label'] ?? class_basename($record->purchasable_type))
                ->sortable(),
            TextColumn::make('title_snapshot')
                ->label(__('admin.labels.title'))
                ->searchable()
                ->url(function (OrderItem $record): ?string {
                    if ($record->purchasable_type === CourseGroup::class) {
                        $courseId = $record->metadata_snapshot['course_id'] ?? $record->purchasable?->course_id;

                        return $courseId ? CourseResource::getUrl('edit', ['record' => $courseId]) : null;
                    }

                    if ($record->purchasable_type === Bundle::class && $record->purchasable) {
                        return BundleResource::getUrl('edit', ['record' => $record->purchasable_id]);
                    }

                    return null;
                })
                ->color('primary'),
            TextColumn::make('course_group_title')
                ->label(__('admin.labels.course_group'))
                ->state(function (OrderItem $record): ?string {
                    if ($record->purchasable_type === CourseGroup::class) {
                        return $record->purchasable?->title;
                    }

                    if ($record->purchasable_type === Bundle::class) {
                        $groupIds = collect(data_get($record->metadata_snapshot, 'selected_course_group_ids', []))
                            ->filter()
                            ->map(fn ($id): int => (int) $id)
                            ->unique();

                        if ($groupIds->isEmpty()) {
                            return null;
                        }

                        return CourseGroup::query()
                            ->whereIn('id', $groupIds)
                            ->get()
                            ->map(fn (CourseGroup $group): string => $group->title)
                            ->implode(', ');
                    }

                    return null;
                })
                ->url(fn (OrderItem $record): ?string => $record->purchasable_type === CourseGroup::class && $record->purchasable
                    ? CourseGroupResource::getUrl('edit', ['record' => $record->purchasable_id])
                    : null)
                ->color('primary')
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('price_amount')->label(__('admin.labels.unit_price'))->sortable(),
            TextColumn::make('quantity')->sortable(),
            TextColumn::make('currency')->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->headerActions([
            Action::make('add_item')
                ->label(__('admin.actions.add_order_item'))
                ->schema(fn (Schema $schema): Schema => OrderItemEditForm::configure($schema, includeQuantity: true))
                ->visible(fn (): bool => $this->getOwnerRecord()->status === OrderStatus::Paid
                    && (Filament::auth()->user()?->can('order_items.update_paid') ?? false))
                ->action(function (array $data): void {
                    abort_unless(Filament::auth()->user()?->can('order_items.update_paid'), 403);

                    $order = $this->getOwnerRecord();
                    try {
                        $diff = app(OrderItemChangeService::class)->addItem($order, $data);
                    } catch (ValidationException $e) {
                        $this->rethrowMountedActionValidation($e);
                    }

                    OrderActivityLog::query()->create([
                        'order_id' => $order->id,
                        'user_id' => Filament::auth()->user()?->id,
                        'diff' => $diff,
                        'notes' => $data['change_reason'],
                        'created_at' => now(),
                    ]);
                }),
        ])->actions([
            Action::make('edit_item')
                ->label(__('admin.actions.edit'))
                ->schema(fn (Schema $schema): Schema => OrderItemEditForm::configure($schema))
                ->fillForm(fn (OrderItem $record): array => [
                    'purchasable_type' => $record->purchasable_type,
                    'purchasable_id' => $record->purchasable_id,
                    'price_amount' => $record->price_amount,
                    'bundle_course_groups' => $record->purchasable_type === Bundle::class
                        ? data_get($record->metadata_snapshot, 'selected_course_groups', [])
                        : [],
                ])
                ->authorize('update')
                ->visible(fn (OrderItem $record): bool => $this->getOwnerRecord()->status === OrderStatus::Paid
                    && $record->seats()->count() <= 1)
                ->action(function (OrderItem $record, array $data): void {
                    try {
                        $diff = app(OrderItemChangeService::class)->updateItem($record, $data);
                    } catch (ValidationException $e) {
                        $this->rethrowMountedActionValidation($e);
                    }

                    OrderActivityLog::query()->create([
                        'order_id' => $this->getOwnerRecord()->id,
                        'user_id' => Filament::auth()->user()?->id,
                        'diff' => $diff,
                        'notes' => $data['change_reason'],
                        'created_at' => now(),
                    ]);
                }),
            DeleteAction::make()->label(__('admin.actions.delete'))
                ->visible(fn (): bool => $this->getOwnerRecord()->status !== OrderStatus::Paid),
        ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_items.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_items.description'));
    }

    /**
     * Re-key a ValidationException's messages under the mounted action's schema state
     * path (e.g. "mountedActions.0.data.purchasable_id"), so Filament highlights the
     * offending field in the action modal instead of the error landing unattached.
     */
    private function rethrowMountedActionValidation(ValidationException $e): void
    {
        $statePath = $this->getMountedActionSchema()?->getStatePath();

        if (blank($statePath)) {
            throw $e;
        }

        throw ValidationException::withMessages(
            collect($e->errors())
                ->mapWithKeys(fn (array $messages, string $key): array => ["{$statePath}.{$key}" => $messages])
                ->all()
        );
    }
}
