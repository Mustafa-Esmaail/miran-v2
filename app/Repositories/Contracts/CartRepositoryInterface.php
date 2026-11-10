<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;

interface CartRepositoryInterface
{
    public function getOrCreateForUser(int $userId): Cart;

    public function getOrCreateForSession(string $sessionId): Cart;

    public function getCartWithItems(int $userId): ?Cart;

    public function getCartWithItemsBySession(string $sessionId): ?Cart;

    public function clearCart(int $cartId): void;

    /**
     * Merges the guest cart identified by $sessionId into the user's cart,
     * then removes the guest cart. No-op if no guest cart exists for that session.
     */
    public function mergeSessionCartIntoUser(string $sessionId, int $userId): void;
}
