<?php

namespace Tests\Unit\Services;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponTargetType;
use App\Enums\CouponType;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponTarget;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CouponRepositoryInterface;
use App\Services\PricingService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class PricingServiceCouponAppliesToTest extends TestCase
{
    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $repo = Mockery::mock(CouponRepositoryInterface::class);
        $repo->shouldReceive('countRedemptions')->andReturn(0);
        $repo->shouldReceive('countUserRedemptions')->andReturn(0);

        $this->pricing = new PricingService($repo);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function makeCoupon(CouponAppliesTo $appliesTo, array $targets = []): Coupon
    {
        $coupon = new Coupon;
        $coupon->forceFill([
            'is_active' => true,
            'type' => CouponType::Percent,
            'applies_to' => $appliesTo,
            'value' => 10,
        ]);
        $coupon->setRelation('targets', new Collection($targets));

        return $coupon;
    }

    private function courseGroupItem(int $categoryId = 1, int $courseId = 1): CartItem
    {
        $course = new Course;
        $course->forceFill(['id' => $courseId, 'category_id' => $categoryId]);

        $group = new CourseGroup;
        $group->forceFill(['id' => $courseId, 'course_id' => $courseId]);
        $group->setRelation('course', $course);

        $item = new CartItem;
        $item->forceFill(['purchasable_type' => CourseGroup::class, 'purchasable_id' => $courseId, 'price_amount' => 1000, 'quantity' => 1]);
        $item->setRelation('purchasable', $group);

        return $item;
    }

    private function bundleItem(int $categoryId = 1): CartItem
    {
        $bundle = new Bundle;
        $bundle->forceFill(['id' => 1, 'category_id' => $categoryId]);

        $item = new CartItem;
        $item->forceFill(['purchasable_type' => Bundle::class, 'purchasable_id' => 1, 'price_amount' => 1000, 'quantity' => 1]);
        $item->setRelation('purchasable', $bundle);

        return $item;
    }

    private function cartWithItems(array $items): Cart
    {
        $cart = new Cart;
        $cart->forceFill(['user_id' => 1]);
        $cart->setRelation('items', new Collection($items));

        return $cart;
    }

    public function test_course_only_coupon_accepts_cart_with_only_course_items(): void
    {
        $coupon = $this->makeCoupon(CouponAppliesTo::Course);
        $cart = $this->cartWithItems([$this->courseGroupItem()]);

        $this->assertTrue($this->pricing->isCouponValid($coupon, 1, 1000, $cart));
    }

    public function test_course_only_coupon_rejects_cart_with_bundle_item(): void
    {
        $coupon = $this->makeCoupon(CouponAppliesTo::Course);
        $cart = $this->cartWithItems([$this->bundleItem()]);

        $this->assertFalse($this->pricing->isCouponValid($coupon, 1, 1000, $cart));
        $this->assertSame('not_applicable', $this->pricing->couponRejectionReason($coupon, 1, 1000, $cart));
    }

    public function test_bundle_only_coupon_rejects_cart_with_course_item(): void
    {
        $coupon = $this->makeCoupon(CouponAppliesTo::Bundle);
        $cart = $this->cartWithItems([$this->courseGroupItem()]);

        $this->assertFalse($this->pricing->isCouponValid($coupon, 1, 1000, $cart));
    }

    public function test_mixed_cart_rejects_course_only_coupon_even_with_one_matching_item(): void
    {
        $coupon = $this->makeCoupon(CouponAppliesTo::Course);
        $cart = $this->cartWithItems([$this->courseGroupItem(), $this->bundleItem()]);

        $this->assertFalse($this->pricing->isCouponValid($coupon, 1, 2000, $cart));
    }

    public function test_both_coupon_accepts_mixed_cart(): void
    {
        $coupon = $this->makeCoupon(CouponAppliesTo::Both);
        $cart = $this->cartWithItems([$this->courseGroupItem(), $this->bundleItem()]);

        $this->assertTrue($this->pricing->isCouponValid($coupon, 1, 2000, $cart));
    }

    public function test_bundle_coupon_with_category_target_rejects_mismatched_category(): void
    {
        $target = new CouponTarget;
        $target->forceFill(['target_type' => CouponTargetType::Category, 'target_id' => 5]);

        $coupon = $this->makeCoupon(CouponAppliesTo::Bundle, [$target]);
        $cart = $this->cartWithItems([$this->bundleItem(categoryId: 99)]);

        $this->assertFalse($this->pricing->isCouponValid($coupon, 1, 1000, $cart));
    }

    public function test_bundle_coupon_with_category_target_accepts_matching_category(): void
    {
        $target = new CouponTarget;
        $target->forceFill(['target_type' => CouponTargetType::Category, 'target_id' => 5]);

        $coupon = $this->makeCoupon(CouponAppliesTo::Bundle, [$target]);
        $cart = $this->cartWithItems([$this->bundleItem(categoryId: 5)]);

        $this->assertTrue($this->pricing->isCouponValid($coupon, 1, 1000, $cart));
    }

    public function test_course_target_accepts_cart_containing_only_the_targeted_course(): void
    {
        $target = new CouponTarget;
        $target->forceFill(['target_type' => CouponTargetType::Course, 'target_id' => 1]);

        $coupon = $this->makeCoupon(CouponAppliesTo::Course, [$target]);
        $cart = $this->cartWithItems([$this->courseGroupItem(courseId: 1)]);

        $this->assertTrue($this->pricing->isCouponValid($coupon, 1, 1000, $cart));
    }

    public function test_course_target_rejects_whole_cart_when_one_item_is_not_targeted(): void
    {
        $target = new CouponTarget;
        $target->forceFill(['target_type' => CouponTargetType::Course, 'target_id' => 1]);

        $coupon = $this->makeCoupon(CouponAppliesTo::Course, [$target]);
        $cart = $this->cartWithItems([
            $this->courseGroupItem(courseId: 1),
            $this->courseGroupItem(courseId: 2),
        ]);

        $this->assertFalse($this->pricing->isCouponValid($coupon, 1, 2000, $cart));
        $this->assertSame('not_applicable', $this->pricing->couponRejectionReason($coupon, 1, 2000, $cart));
    }
}
