<?php

namespace App\Livewire\Checkout;

use App\Exceptions\DuplicatePurchaseException;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use App\Services\Settings\SettingsService;
use App\Support\Gtm\GtmEventQueue;
use App\Support\Gtm\GtmItemFormatter;
use App\ViewModels\CartViewModel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CheckoutForm extends Component
{
    public string $paymentMethod = 'tamara';

    public string $firstName = '';

    public string $lastName = '';

    public string $phone = '';

    public string $email = '';

    public string $password = '';

    public array $availableMethods = [];

    public string $couponCode = '';

    public ?string $message = null;

    public ?string $orderErrorMessage = null;

    public bool $showOrderErrorModal = false;

    /**
     * @var array<int, array<int, string>>
     */
    public array $seatAssignments = [];

    /**
     * @var array<int, array<int, string>> item ID -> parallel email per seat index (optional)
     */
    public array $seatEmails = [];

    /** @var array<int, string> cart item ID -> invitee email for gifted/duplicate items */
    public array $inviteEmails = [];

    /** @var array<int, string> cart item ID -> invitee phone (optional, for gifted/duplicate items) */
    public array $invitePhones = [];

    /** @var array<int, int> cart item IDs flagged as an exact re-purchase of the same course group (invite/gift flow) */
    public array $duplicateItemIds = [];

    /** @var array<int, int> cart item IDs flagged as owning the same course under a different group (confirm/back popup) */
    public array $differentGroupDuplicateItemIds = [];

    /** @var array<int, int> cart item IDs the buyer confirmed to purchase despite already owning the course */
    public array $confirmedDuplicateItemIds = [];

    public bool $showDuplicateInvitePanel = false;

    public bool $showDuplicateModal = false;

    public bool $showLoginPanel = false;

    public string $loginPassword = '';

    public function mount(
        SettingsService $settingsService,
        CheckoutService $checkoutService,
        CartService $cartService
    ): void {
        if (auth()->check()) {
            $user = auth()->user();
            $this->firstName = $user->name ?? '';
            $this->email = $user->email ?? '';
            $this->phone = $this->normalizeLocalPhone($user->phone ?? '');
        }

        $cartViewModel = CartViewModel::make($this->owner(), $cartService, true);
        $this->couponCode = (string) ($cartViewModel->appliedCoupon ?? '');
        $this->refreshAvailableMethods($settingsService, $checkoutService, $cartViewModel->totals['totalAmount'] ?? 0);

        if ($cartViewModel->items !== []) {
            $this->dispatch('gtm-event', name: 'begin_checkout', data: [
                'currency' => $cartViewModel->totals['currency'] ?? 'SAR',
                'value' => $cartViewModel->totals['totalAmount'] ?? GtmItemFormatter::valueOf($cartViewModel->items),
                'items' => GtmItemFormatter::fromCartItems($cartViewModel->items),
            ]);
        }
    }

    protected function rules(): array
    {
        $rules = [
            'firstName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,filter', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^5\d{8}$/'],
            'paymentMethod' => ['required', Rule::in(array_keys($this->availableMethods))],
        ];

        if (! auth()->check()) {
            $rules['email'][] = Rule::unique('users', 'email');
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        return $rules;
    }

    public function placeOrder(CheckoutService $checkoutService, PaymentService $payments, CartService $cartService): void
    {
        if (! auth()->check()) {
            $this->validate();

            $guestSessionId = session()->getId();

            try {
                $user = User::create([
                    'name' => trim($this->firstName.' '.$this->lastName),
                    'email' => $this->email,
                    'phone' => $this->phone ?: null,
                    'password' => Hash::make($this->password),
                    'locale' => app()->getLocale(),
                ]);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateEmailViolation($exception)) {
                    throw $exception;
                }

                // Two concurrent submissions can both pass the pre-insert uniqueness check
                // and race to the same email; the second one lands here instead of erroring out.
                $this->addError('email', __('web.checkout.email_already_registered'));
                $this->setOrderError(__('web.checkout.email_already_registered'));

                return;
            }
            $user->assignRole(Role::findOrCreate('student', 'web'));
            Auth::guard('web')->login($user);

            $cartService->mergeGuestCartIntoUser($guestSessionId, $user->id);
        }

        $vm = CartViewModel::make($this->owner(), $cartService, true);
        $this->refreshAvailableMethods($this->resolveSettingsService(), $checkoutService, $vm->totals['totalAmount'] ?? 0);

        if (empty($this->availableMethods)) {
            $this->addError('paymentMethod', __('web.checkout.no_payment_methods'));
            $this->setOrderError(__('web.checkout.no_payment_methods'));

            return;
        }

        $this->validate();

        try {
            $normalizedAssignments = $this->validateSeatAssignments($vm->items);
        } catch (\RuntimeException $exception) {
            $this->addError('paymentMethod', $exception->getMessage());
            $this->setOrderError($exception->getMessage());

            return;
        }

        try {
            $this->validateInviteEmails();
        } catch (\RuntimeException $exception) {
            $this->addError('paymentMethod', $exception->getMessage());
            $this->setOrderError($exception->getMessage());

            return;
        }

        try {
            $order = $checkoutService->createOrderFromCart(auth()->id(), $normalizedAssignments, $this->inviteEmails, $this->invitePhones, $this->seatEmails, trim($this->firstName.' '.$this->lastName), $this->phone ?: null, $this->confirmedDuplicateItemIds);
        } catch (DuplicatePurchaseException $e) {
            $sameGroupIds = [];
            $differentGroupIds = [];
            foreach ($e->duplicateItems as $duplicate) {
                if (($duplicate['type'] ?? null) === 'same_group') {
                    $sameGroupIds[] = $duplicate['cartItemId'];
                } else {
                    $differentGroupIds[] = $duplicate['cartItemId'];
                }
            }

            $this->duplicateItemIds = $sameGroupIds;
            $this->showDuplicateInvitePanel = $sameGroupIds !== [];

            $this->differentGroupDuplicateItemIds = $differentGroupIds;
            $this->showDuplicateModal = $differentGroupIds !== [];

            // dd($this->duplicateItemIds, $this->differentGroupDuplicateItemIds, $this->showDuplicateInvitePanel, $this->showDuplicateModal);
            // $this->dispatch('toast', message: __('web.checkout.already_own_notice'));

            return;
        } catch (\RuntimeException $exception) {
            $this->addError('paymentMethod', $exception->getMessage());
            $this->setOrderError($exception->getMessage());

            return;
        }
        if (! $order) {
            $this->addError('paymentMethod', __('web.cart.empty'));
            $this->setOrderError(__('web.cart.empty'));

            return;
        }

        if ($this->paymentMethod === 'free') {
            try {
                $payments->completeFreePayment($order);
            } catch (\RuntimeException $exception) {
                $this->addError('paymentMethod', $exception->getMessage());
                $this->setOrderError($exception->getMessage());

                return;
            }

            session()->flash('order_success', true);
            session()->flash('gtm_purchase_order_id', $order->id);
            $this->redirectRoute('dashboard.orders.show', ['order_no' => $order->order_no], navigate: false);

            return;
        }

        if ($this->paymentMethod === 'cash') {
            $cartService->clearCart($this->owner());
            session()->flash('order_success', true);
            session()->flash('gtm_purchase_order_id', $order->id);
            $this->redirectRoute('dashboard.orders.show', ['order_no' => $order->order_no], navigate: false);

            return;
        }

        if ($this->paymentMethod === 'bank_transfer') {
            $cartService->clearCart($this->owner());
            GtmEventQueue::push('add_payment_info', [
                'currency' => $vm->totals['currency'] ?? 'SAR',
                'value' => $vm->totals['totalAmount'] ?? GtmItemFormatter::valueOf($vm->items),
                'payment_type' => 'bank_transfer',
                'items' => GtmItemFormatter::fromCartItems($vm->items),
            ]);
            $this->redirectRoute('bank-transfer.upload', ['order_no' => $order->order_no], navigate: false);

            return;
        }

        try {
            $paymentResult = $payments->startPayment($order, $this->paymentMethod);
        } catch (\RuntimeException $exception) {
            $this->addError('paymentMethod', $exception->getMessage());
            $this->setOrderError($exception->getMessage());

            return;
        }
        $redirectUrl = (string) ($paymentResult['redirect_url'] ?? '');
        if ($redirectUrl !== '' && $redirectUrl !== '#') {
            GtmEventQueue::push('add_payment_info', [
                'currency' => $vm->totals['currency'] ?? 'SAR',
                'value' => $vm->totals['totalAmount'] ?? GtmItemFormatter::valueOf($vm->items),
                'payment_type' => $this->paymentMethod,
                'items' => GtmItemFormatter::fromCartItems($vm->items),
            ]);

            // If the user bails from the gateway instead of letting it redirect back,
            // the next dashboard.orders visit will bounce them straight to this order.
            session(['pending_payment_order_no' => $order->order_no]);

            // Route through a lightweight buffer page instead of jumping straight to the
            // gateway: it gives the browser a plain, stateless page to land on if the user
            // hits back from the gateway, instead of a stale Livewire checkout-form snapshot.
            $this->redirectRoute('dashboard.orders.redirecting', ['order_no' => $order->order_no], navigate: false);

            return;
        }

        session()->flash('order_success', true);
        session()->flash('gtm_purchase_order_id', $order->id);
        $this->redirectRoute('dashboard.orders.show', ['order_no' => $order->order_no], navigate: false);
    }

    public function updatedEmail(): void
    {
        if (auth()->check()) {
            return;
        }

        $this->showLoginPanel = User::query()->where('email', trim($this->email))->exists();
        $this->loginPassword = '';
    }

    public function loginAndContinue(CartService $cartService): void
    {
        $guestSessionId = session()->getId();

        if (Auth::guard('web')->attempt(['email' => trim($this->email), 'password' => $this->loginPassword])) {
            $cartService->mergeGuestCartIntoUser($guestSessionId, auth()->id());

            $user = auth()->user();
            $this->firstName = (string) ($user->name ?? '');
            $this->phone = (string) ($user->phone ?? '');
            $this->showLoginPanel = false;
            $this->loginPassword = '';
            $this->resetErrorBag();
        } else {
            $this->addError('loginPassword', __('auth.failed'));
        }
    }

    public function closeOrderErrorModal(): void
    {
        $this->showOrderErrorModal = false;
    }

    public function dismissDuplicateInvitePanel(): void
    {
        $this->showDuplicateInvitePanel = false;
        $this->duplicateItemIds = [];
        $this->inviteEmails = [];
        $this->invitePhones = [];
    }

    public function closeDuplicateModal(): void
    {
        $this->showDuplicateModal = false;
        $this->differentGroupDuplicateItemIds = [];
    }

    public function confirmDuplicateAndContinue(CheckoutService $checkoutService, PaymentService $payments, CartService $cartService): void
    {
        $this->confirmedDuplicateItemIds = array_values(array_unique(array_merge(
            $this->confirmedDuplicateItemIds,
            $this->differentGroupDuplicateItemIds
        )));
        $this->showDuplicateModal = false;
        $this->differentGroupDuplicateItemIds = [];

        $this->placeOrder($checkoutService, $payments, $cartService);
    }

    public function applyCoupon(CartService $cartService): void
    {
        $this->resetErrorBag('couponCode');
        $this->message = null;

        $this->couponCode = strtoupper(trim($this->couponCode));
        if ($this->couponCode === '') {
            $this->addError('couponCode', __('web.cart.coupon_required'));

            return;
        }

        $reason = $cartService->applyCoupon($this->owner(), $this->couponCode);
        $this->message = match ($reason) {
            null => __('web.cart.coupon_applied'),
            'not_applicable' => __('web.cart.coupon_not_applicable'),
            default => __('web.cart.coupon_invalid'),
        };

        if ($reason === null) {
            $totals = $cartService->getTotals($this->owner());
            $this->dispatch('gtm-event', name: 'apply_coupon', data: [
                'coupon' => $this->couponCode,
                'currency' => $totals?->currency ?? 'SAR',
                'value' => $totals?->discountAmount ?? 0,
            ]);
        }

        $this->dispatch('toast', message: $this->message);
    }

    public function removeCoupon(CartService $cartService): void
    {
        $previousCode = $cartService->getAppliedCouponCode($this->owner());
        $this->couponCode = '';
        $this->message = null;
        $cartService->removeCoupon($this->owner());

        if ($previousCode) {
            $this->dispatch('gtm-event', name: 'remove_coupon', data: ['coupon' => $previousCode]);
        }

        $this->dispatch('toast', message: __('web.checkout.remove_coupon'));
    }

    public function removeItem(int $itemId, CartService $cartService): void
    {
        $cartService->removeItem($this->owner(), $itemId);
    }

    public function addSeatPhone(int $itemId, int $maxSeats): void
    {
        $phones = $this->seatAssignments[$itemId] ?? [];
        $phones = array_values($phones);

        if (count($phones) >= $maxSeats) {
            return;
        }

        $phones[] = '';
        $this->seatAssignments[$itemId] = $phones;

        $emails = array_values($this->seatEmails[$itemId] ?? []);
        $emails[] = '';
        $this->seatEmails[$itemId] = $emails;
    }

    public function removeSeatPhone(int $itemId, int $index): void
    {
        $phones = $this->seatAssignments[$itemId] ?? [];
        if (! array_key_exists($index, $phones)) {
            return;
        }

        unset($phones[$index]);
        $this->seatAssignments[$itemId] = array_values($phones);

        $emails = $this->seatEmails[$itemId] ?? [];
        unset($emails[$index]);
        $this->seatEmails[$itemId] = array_values($emails);
    }

    public function render(CartService $cartService): View
    {
        $vm = CartViewModel::make($this->owner(), $cartService, true);

        $displayItems = $vm->items;

        $couponDiscountTotal = $vm->totals['discountAmount'] ?? 0;
        $displayTotal = $vm->totals['totalAmount'] ?? collect($displayItems)->sum('lineTotal');

        $this->refreshAvailableMethods($this->resolveSettingsService(), $this->resolveCheckoutService(), (int) $displayTotal);
        $this->syncSeatAssignments($displayItems);
        $currency = $vm->totals['currency'] ?? 'SAR';
        $itemDiscountTotal = collect($displayItems)->sum(function (array $item) {
            $original = $item['originalPriceAmount'] ?? $item['priceAmount'];
            $perItemDiscount = max(0, $original - $item['priceAmount']);

            return $perItemDiscount * $item['quantity'];
        });
        [$onlineMethods, $bankMethods, $installmentMethods] = $this->partitionMethods($this->availableMethods);
        $settings = app(SettingsService::class);
        $methodLogos = collect($settings->enabledPaymentMethods())
            ->keyBy('key')
            ->map(fn (array $m): ?string => $m['logo'])
            ->all();

        return view('livewire.checkout.checkout-form')
            ->with([
                'vm' => $vm,
                'displayItems' => $displayItems,
                'displayTotal' => $displayTotal,
                'currency' => $currency,
                'itemDiscountTotal' => $itemDiscountTotal,
                'couponDiscountTotal' => $couponDiscountTotal,
                'onlineMethods' => $onlineMethods,
                'bankMethods' => $bankMethods,
                'installmentMethods' => $installmentMethods,
                'methodLogos' => $methodLogos,
                'appliedCoupon' => $vm->appliedCoupon,
            ])
            ->layout('layouts.app');
    }

    private function partitionMethods(array $methods): array
    {
        $onlineMethods = [];
        $bankMethods = [];
        $installmentMethods = [];

        foreach ($methods as $key => $label) {
            if (in_array($key, ['bank_transfer', 'cash'], true)) {
                $bankMethods[$key] = $label;
            } elseif (in_array($key, ['tamara', 'tabby'], true)) {
                $installmentMethods[$key] = $label;
            } else {
                $onlineMethods[$key] = $label;
            }
        }

        return [$onlineMethods, $bankMethods, $installmentMethods];
    }

    private function setOrderError(string $message): void
    {
        $this->orderErrorMessage = $message;
        $this->showOrderErrorModal = true;
    }

    private function refreshAvailableMethods(SettingsService $settingsService, CheckoutService $checkoutService, int $totalAmount): void
    {
        $this->availableMethods = $checkoutService->getAvailablePaymentMethods($settingsService, $totalAmount <= 0);

        if (! array_key_exists($this->paymentMethod, $this->availableMethods)) {
            $this->paymentMethod = array_key_first($this->availableMethods) ?? '';
        }
    }

    private function resolveSettingsService(): SettingsService
    {
        return app(SettingsService::class);
    }

    private function resolveCheckoutService(): CheckoutService
    {
        return app(CheckoutService::class);
    }

    /**
     * @param  array<int, array{id:int, quantity:int}>  $items
     */
    private function syncSeatAssignments(array $items): void
    {
        $itemIds = collect($items)
            ->filter(fn (array $item) => (int) $item['quantity'] > 1)
            ->pluck('id')
            ->all();

        foreach ($itemIds as $itemId) {
            if (! array_key_exists($itemId, $this->seatAssignments)) {
                $this->seatAssignments[$itemId] = [];
            }
            if (! array_key_exists($itemId, $this->seatEmails)) {
                $this->seatEmails[$itemId] = [];
            }
        }

        foreach (array_keys($this->seatAssignments) as $itemId) {
            if (! in_array($itemId, $itemIds, true)) {
                unset($this->seatAssignments[$itemId], $this->seatEmails[$itemId]);
            }
        }
    }

    /**
     * @param  array<int, array{id:int, quantity:int}>  $items
     * @return array<int, array<int, string>>
     */
    private function validateSeatAssignments(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $itemId = $item['id'];
            $quantity = (int) $item['quantity'];
            if ($quantity <= 1) {
                continue;
            }
            $inviteableSeats = max(0, $quantity - 1);
            $phones = $this->seatAssignments[$itemId] ?? [];
            $phones = array_filter(array_map('trim', $phones));
            $phones = array_values($phones);

            if (count($phones) > $inviteableSeats) {
                throw new \RuntimeException(__('web.checkout.invite_limit_reached'));
            }

            if (count($phones) !== count(array_unique($phones))) {
                throw new \RuntimeException(__('web.checkout.invite_duplicate_phone'));
            }

            if ($phones !== []) {
                Validator::make(['phones' => $phones], [
                    'phones.*' => ['required', 'string', 'min:6', 'max:30', 'regex:/^[0-9+\\-\\s()]+$/'],
                ])->validate();
            }

            $normalized[$itemId] = $phones;
        }

        return $normalized;
    }

    private function validateInviteEmails(): void
    {
        $emails = array_filter(array_map(fn (string $e) => strtolower(trim($e)), $this->inviteEmails));

        if (count($emails) !== count(array_unique($emails))) {
            throw new \RuntimeException(__('web.checkout.invite_duplicate_email'));
        }
    }

    /**
     * Strips any leading +966/966/0 so the field always holds the bare 9-digit local number (5XXXXXXXX).
     */
    private function normalizeLocalPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * The current cart owner: the authenticated user's id, or the guest session id.
     */
    private function owner(): int|string
    {
        return auth()->check() ? auth()->id() : session()->getId();
    }

    private function isDuplicateEmailViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'duplicate key value violates unique constraint');
    }
}
