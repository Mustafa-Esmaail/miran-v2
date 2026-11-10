<?php

namespace App\Repositories\Eloquent;

use App\Models\Bundle;
use App\Models\Cart;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class CartRepository implements CartRepositoryInterface
{
    public function getOrCreateForUser(int $userId): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $userId]);
    }

    public function getOrCreateForSession(string $sessionId): Cart
    {
        return Cart::query()->firstOrCreate(['session_id' => $sessionId]);
    }

    public function getCartWithItems(int $userId): ?Cart
    {
        return $this->withItemsQuery()
            ->where('user_id', $userId)
            ->first();
    }

    public function getCartWithItemsBySession(string $sessionId): ?Cart
    {
        return $this->withItemsQuery()
            ->where('session_id', $sessionId)
            ->first();
    }

    public function clearCart(int $cartId): void
    {
        $cart = Cart::query()->whereKey($cartId)->first();
        if (! $cart) {
            return;
        }

        $cart->items()->delete();
        $cart->update(['coupon_id' => null]);
    }

    public function mergeSessionCartIntoUser(string $sessionId, int $userId): void
    {
        $guestCart = Cart::query()->where('session_id', $sessionId)->with('items')->first();
        if (! $guestCart) {
            return;
        }

        $userCart = $this->getOrCreateForUser($userId);

        if ($guestCart->id === $userCart->id) {
            return;
        }

        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $item) {
                $userCart->items()->updateOrCreate(
                    [
                        'purchasable_type' => $item->purchasable_type,
                        'purchasable_id' => $item->purchasable_id,
                    ],
                    [
                        'price_amount' => $item->price_amount,
                        'quantity' => $item->quantity,
                        'selection_snapshot' => $item->selection_snapshot,
                    ]
                );
            }

            if ($guestCart->coupon_id) {
                $userCart->update(['coupon_id' => $guestCart->coupon_id]);
            }

            $guestCart->delete();
        });
    }

    private function withItemsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Cart::query()->with([
            'items.purchasable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    CourseGroup::class => ['course.discounts'],
                    Bundle::class => ['discounts', 'courses'],
                ]);
            },
            'coupon',
        ]);
    }
}
