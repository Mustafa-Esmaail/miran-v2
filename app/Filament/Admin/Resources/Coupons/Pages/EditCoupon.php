<?php

namespace App\Filament\Admin\Resources\Coupons\Pages;

use App\Enums\CouponTargetType;
use App\Filament\Admin\Resources\Coupons\CouponResource;
use App\Models\Coupon;
use Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    /**
     * @var array<int, int|string>
     */
    protected array $pendingCategoryIds = [];

    /**
     * @var array<int, int|string>
     */
    protected array $pendingCourseIds = [];

    /**
     * @var array<int, int|string>
     */
    protected array $pendingBundleIds = [];

    public function getBreadcrumb(): string
    {
        return __('admin.pages.edit');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.edit_title', ['label' => $this->getRecordTitle()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Coupon $record */
        $record = $this->record;
        $targets = $record->targets;

        $data['category_ids'] = $targets->where('target_type', CouponTargetType::Category)->pluck('target_id')->all();
        $data['course_ids'] = $targets->where('target_type', CouponTargetType::Course)->pluck('target_id')->all();
        $data['bundle_ids'] = $targets->where('target_type', CouponTargetType::Bundle)->pluck('target_id')->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingCategoryIds = $data['category_ids'] ?? [];
        $this->pendingCourseIds = $data['course_ids'] ?? [];
        $this->pendingBundleIds = $data['bundle_ids'] ?? [];
        unset($data['category_ids'], $data['course_ids'], $data['bundle_ids']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Coupon $record */
        $record = $this->record;

        CouponResource::syncTargets($record, $this->pendingCategoryIds, $this->pendingCourseIds, $this->pendingBundleIds);
    }
}
