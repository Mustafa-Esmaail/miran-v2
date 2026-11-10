<?php

namespace App\Filament\Admin\Resources\Coupons\Pages;

use App\Filament\Admin\Resources\Coupons\CouponResource;
use App\Models\Coupon;
use Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
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
        return __('admin.pages.create');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.pages.create_title', ['resource' => static::getResource()::getTitleCaseModelLabel()]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingCategoryIds = $data['category_ids'] ?? [];
        $this->pendingCourseIds = $data['course_ids'] ?? [];
        $this->pendingBundleIds = $data['bundle_ids'] ?? [];
        unset($data['category_ids'], $data['course_ids'], $data['bundle_ids']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Coupon $record */
        $record = $this->record;

        CouponResource::syncTargets($record, $this->pendingCategoryIds, $this->pendingCourseIds, $this->pendingBundleIds);
    }
}
