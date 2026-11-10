<div class="bg-white text-[var(--text-strong)] full-bleed">
    <section class="app-container py-10">

        {{-- Top links --}}
        <div class="mb-8 border-b border-gray-200 pb-6 text-sm space-y-1 text-right">
            <p>
                {{ __('web.checkout.have_coupon') }}
                <a href="#" x-data @click.prevent="$dispatch('toggle-coupon-panel')"
                    class="font-bold text-[var(--accent-500)] hover:underline">
                    {{ __('web.checkout.click_here_coupon') }}
                </a>
            </p>
        </div>

        {{-- Collapsible coupon panel --}}
        <div x-data="{ open: false }" @toggle-coupon-panel.window="open = !open">
            <div x-show="open" x-transition
                class="mb-6 rounded border border-[var(--accent-500)] bg-[color-mix(in_srgb,var(--accent-500)_6%,white)] p-4">
                <div class="flex items-center gap-2" x-data="{ couponCode: @entangle('couponCode').live }">
                    <input type="text" x-model="couponCode" placeholder="{{ __('web.cart.coupon_placeholder') }}"
                        class="flex-1 rounded border border-gray-300 px-3 py-2 text-sm focus:border-[var(--accent-500)] focus:outline-none">
                    @if ($appliedCoupon)
                        <button type="button" wire:click="removeCoupon"
                            class="rounded bg-[var(--accent-500)] px-4 py-2 text-sm font-semibold text-white hover:brightness-95">
                            {{ __('web.checkout.remove_coupon') }}
                        </button>
                    @else
                        <button type="button" wire:click="applyCoupon" wire:loading.attr="disabled"
                            :disabled="couponCode.trim().length === 0"
                            class="rounded bg-[var(--accent-500)] px-4 py-2 text-sm font-semibold text-white hover:brightness-95 disabled:opacity-50">
                            {{ __('web.cart.apply') }}
                        </button>
                    @endif
                </div>
                @error('couponCode')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @if ($message)
                    <div class="mt-2 text-xs text-[var(--accent-500)]">{{ $message }}</div>
                @endif
                @if ($appliedCoupon)
                    <div class="mt-2 text-xs text-[var(--text-faded)]">
                        {{ __('web.cart.coupon_applied_label', ['code' => $appliedCoupon]) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Main two-column layout --}}
        {{-- In RTL: first child appears on RIGHT, second on LEFT --}}
        <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_420px]">

            {{-- Billing form (RIGHT side in RTL) --}}
            <div>
                <h2 class="mb-6 text-xl font-bold text-[var(--text-heading)]">
                    {{ __('web.checkout.invoice_details') }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    {{-- First name --}}
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-strong)] mb-1">
                            {{ __('web.checkout.first_name') }} *
                        </label>
                        <input type="text" wire:model="firstName"
                            placeholder="{{ __('web.checkout.first_name_placeholder') }}"
                            class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-[var(--text-strong)] focus:border-[var(--accent-500)] focus:outline-none">
                        @error('firstName')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Last name --}}
                    <div>
                        <label class="block text-sm font-medium text-[var(--text-strong)] mb-1">
                            {{ __('web.checkout.last_name') }} *
                        </label>
                        <input type="text" wire:model="lastName"
                            placeholder="{{ __('web.checkout.last_name_placeholder') }}"
                            class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-[var(--text-strong)] focus:border-[var(--accent-500)] focus:outline-none">
                    </div>

                    {{-- Phone --}}
                    <div class="sm:col-span-2" x-data="{
                            ph: @entangle('phone').live,
                            get ok() { return /^5\d{8}$/.test((this.ph || '').replace(/\s/g, '')); }
                        }">
                        <label class="block text-sm font-medium text-[var(--text-strong)] mb-1">
                            {{ __('web.checkout.phone') }} *
                        </label>
                        <div class="flex items-center gap-3 rounded border border-gray-300 bg-white px-3 py-2.5 rtl:flex-row-reverse"
                            :class="{ 'border-green-500': ph.trim() !== '' && ok, 'border-red-400': ph.trim() !== '' && !ok }">
                            <img src="/img/sa-flag.png" alt="SA" class="h-5 w-5 shrink-0 rounded-full object-cover">
                            <span class="text-sm font-semibold text-gray-400">+966</span>
                            <span class="h-6 w-px bg-gray-300"></span>
                            <input type="tel" x-model="ph"
                                placeholder="{{ __('web.checkout.phone_placeholder') }}"
                                class="ltr-input w-full border-0 bg-transparent text-sm text-[var(--text-strong)] placeholder:text-gray-400 focus:outline-none focus:ring-0">
                            <svg x-show="ph.trim() !== '' && ok" style="display:none" class="h-4 w-4 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            <svg x-show="ph.trim() !== '' && !ok" style="display:none" class="h-4 w-4 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        @error('phone')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-400">{{ __('web.checkout.phone_hint') }}</p>
                    </div>

                    {{-- Email --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-[var(--text-strong)] mb-1">
                            {{ __('web.checkout.email') }} *
                        </label>
                        <input type="email" @guest wire:model.blur="email" @else wire:model="email" @endguest
                            placeholder="{{ __('web.checkout.email_placeholder') }}"
                            class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-[var(--text-strong)] focus:border-[var(--accent-500)] focus:outline-none">
                        @guest
                            @if (!$showLoginPanel)
                                @error('email')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            @endif
                        @else
                            @error('email')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        @endguest
                    </div>

                    {{-- Inline login panel (guests whose email already exists) --}}
                    @guest
                        @if ($showLoginPanel)
                            <div
                                class="sm:col-span-2 rounded-xl border border-[var(--accent-500)] bg-[color-mix(in_srgb,var(--accent-500)_5%,white)] p-4">
                                <p class="mb-3 text-sm font-semibold text-(--text-strong)">
                                    {{ __('web.checkout.email_exists_login_hint') }}
                                </p>
                                <div class="flex flex-col gap-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-(--text-faded)">
                                            {{ __('web.checkout.password') }}
                                        </label>
                                        <input type="password" wire:model="loginPassword"
                                            wire:keydown.enter="loginAndContinue"
                                            placeholder="{{ __('web.checkout.password_placeholder') }}"
                                            class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm focus:border-[var(--accent-500)] focus:outline-none">
                                        @error('loginPassword')
                                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="button" wire:click="loginAndContinue" wire:loading.attr="disabled"
                                        class="w-full rounded-lg bg-[var(--accent-500)] px-4 py-2.5 text-sm font-semibold text-white hover:brightness-95 disabled:opacity-60">
                                        {{ __('web.checkout.login_and_continue') }}
                                    </button>
                                </div>
                            </div>
                        @endif

                        {{-- Password field only when not showing the login panel --}}
                        @if (!$showLoginPanel)
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-[var(--text-strong)] mb-1">
                                    {{ __('web.checkout.create_account_password') }} *
                                </label>
                                <input type="password" wire:model="password"
                                    placeholder="{{ __('web.checkout.password_placeholder') }}"
                                    class="w-full rounded border border-gray-300 px-3 py-2.5 text-sm text-[var(--text-strong)] focus:border-[var(--accent-500)] focus:outline-none">
                                @error('password')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endguest
                </div>

                {{-- Seat assignments (bulk orders) --}}
                @php $hasSeatAssignments = collect($displayItems)->contains(fn ($item) => $item['quantity'] > 1); @endphp
                @if (count($displayItems) && $hasSeatAssignments)
                    <div class="mt-8 rounded-xl border border-[var(--panel-border)] p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-bold text-[var(--text-strong)]">
                                    {{ __('web.checkout.assign_seats') }}</h3>
                                <p class="mt-1 text-sm text-[var(--text-faded)]">
                                    {{ __('web.checkout.assign_seats_hint') }}</p>
                            </div>
                            <span
                                class="rounded-full bg-[color-mix(in_srgb,var(--accent-500)_10%,white)] px-3 py-1 text-xs font-semibold text-[var(--accent-500)]">
                                {{ __('web.checkout.optional') }}
                            </span>
                        </div>
                        <div class="mt-5 flex flex-col gap-4">
                            @foreach ($displayItems as $item)
                                @if ($item['quantity'] <= 1)
                                    @continue
                                @endif
                                @php
                                    $assignedCount = count($seatAssignments[$item['id']] ?? []);
                                    $inviteableSeats = max(0, $item['quantity'] - 1);
                                    $remainingCount = max(0, $inviteableSeats - $assignedCount);
                                @endphp
                                <div class="rounded-xl border border-[var(--panel-border)] px-4 py-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-semibold text-[var(--text-strong)]">
                                                {{ $item['title'] }}</p>
                                            <p class="text-xs text-[var(--text-faded)]">
                                                {{ __('web.checkout.seat_count', ['count' => $inviteableSeats]) }}</p>
                                        </div>
                                        <div class="text-xs font-semibold text-[var(--text-faded)]">
                                            {{ __('web.checkout.seats_remaining', ['count' => $remainingCount]) }}
                                        </div>
                                    </div>
                                    <div class="mt-4 flex flex-col gap-3">
                                        @foreach ($seatAssignments[$item['id']] ?? [] as $index => $value)
                                            <div class="flex flex-wrap items-center gap-2"
                                                wire:key="seat-{{ $item['id'] }}-{{ $index }}">
                                                <input type="tel"
                                                    wire:model.live="seatAssignments.{{ $item['id'] }}.{{ $index }}"
                                                    placeholder="{{ __('web.checkout.invite_phone_placeholder') }}"
                                                    class="min-w-0 flex-1 rounded border border-gray-300 px-3 py-2 text-sm">
                                                <input type="email"
                                                    wire:model.live="seatEmails.{{ $item['id'] }}.{{ $index }}"
                                                    placeholder="{{ __('web.checkout.invite_email_placeholder') }}"
                                                    class="min-w-0 flex-1 rounded border border-gray-300 px-3 py-2 text-sm">
                                                <button type="button"
                                                    wire:click="removeSeatPhone({{ $item['id'] }}, {{ $index }})"
                                                    class="shrink-0 rounded border border-red-300 px-3 py-2 text-xs font-semibold text-red-500">
                                                    {{ __('web.checkout.remove_phone') }}
                                                </button>
                                            </div>
                                        @endforeach
                                        <button type="button"
                                            wire:click="addSeatPhone({{ $item['id'] }}, {{ max(0, $item['quantity'] - 1) }})"
                                            class="w-fit rounded border border-[var(--accent-500)] px-4 py-2 text-xs font-semibold text-[var(--accent-500)]">
                                            {{ __('web.checkout.add_phone') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Duplicate-purchase invite panel --}}

                @if ($showDuplicateInvitePanel && count($duplicateItemIds))
                    <div class="mt-8 rounded-xl border border-amber-300 bg-amber-50 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-amber-800">
                                        {{ __('web.checkout.already_own_notice') }}</p>
                                    <p class="mt-1 text-xs text-amber-700">{{ __('web.checkout.invite_gift_hint') }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" wire:click="dismissDuplicateInvitePanel"
                                class="text-amber-500 hover:text-amber-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 flex flex-col gap-4">
                            @foreach ($displayItems as $item)
                                @if (in_array($item['id'], $duplicateItemIds))
                                    <div class="rounded-lg border border-amber-200 bg-white px-4 py-3">
                                        <p class="mb-2 text-sm font-semibold text-(--text-strong)">
                                            {{ $item['title'] }}</p>
                                        <div class="flex flex-col gap-2">
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-(--text-faded)">
                                                    {{ __('web.checkout.invite_email_label') }} *
                                                </label>
                                                <input type="email"
                                                    wire:model.live="inviteEmails.{{ $item['id'] }}"
                                                    placeholder="{{ __('web.checkout.already_own_item') }}"
                                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none">
                                                @error('inviteEmails.' . $item['id'])
                                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-(--text-faded)">
                                                    {{ __('web.checkout.invite_phone_label') }}
                                                    <span
                                                        class="text-[10px] text-(--text-faded)">({{ __('web.checkout.optional') }})</span>
                                                </label>
                                                <input type="tel"
                                                    wire:model.live="invitePhones.{{ $item['id'] }}"
                                                    placeholder="{{ __('web.checkout.invite_phone_placeholder') }}"
                                                    class="w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none">
                                                @error('invitePhones.' . $item['id'])
                                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Duplicate-purchase (different group) confirm/back panel --}}
                @if ($showDuplicateModal && count($differentGroupDuplicateItemIds))
                    <div class="mt-8 rounded-xl border border-red-300 bg-red-50 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-red-800">{{ __('web.checkout.duplicate_modal_title') }}</p>
                                    <p class="mt-1 text-xs text-red-700">{{ __('web.checkout.duplicate_modal_body') }}</p>
                                </div>
                            </div>
                            <button type="button" wire:click="closeDuplicateModal" class="text-red-500 hover:text-red-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 flex flex-col gap-2">
                            @foreach ($displayItems as $item)
                                @if (in_array($item['id'], $differentGroupDuplicateItemIds))
                                    <div class="rounded-lg border border-red-200 bg-white px-4 py-3">
                                        <p class="text-sm font-semibold text-(--text-strong)">{{ $item['title'] }}</p>
                                        @if (!empty($item['subtitle']))
                                            <p class="mt-1 text-xs text-(--text-faded)">{{ $item['subtitle'] }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="mt-4 flex flex-col gap-3">
                            <button
                                type="button"
                                wire:click="confirmDuplicateAndContinue"
                                wire:loading.attr="disabled"
                                class="flex h-11 w-full items-center justify-center gap-2.5 rounded-lg bg-red-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
                            >
                                {{ __('web.checkout.duplicate_modal_confirm') }}
                            </button>
                            <button
                                type="button"
                                wire:click="closeDuplicateModal"
                                class="flex h-10 w-full items-center justify-center gap-2.5 rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-(--text-strong)"
                            >
                                {{ __('web.checkout.duplicate_modal_back') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Order box (LEFT side in RTL) --}}
            <div class="rounded border border-gray-300 bg-white overflow-hidden self-start">

                {{-- Header --}}
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-base font-bold text-[var(--text-heading)]">{{ __('web.checkout.your_order') }}
                    </h3>
                </div>

                {{-- Items table --}}
                <div class="px-5">
                    <div
                        class="grid grid-cols-[1fr_auto] py-3 border-b border-gray-200 text-xs font-semibold text-[var(--text-faded)] gap-4">
                        <span>{{ __('web.cart.product') }}</span>
                        <span>{{ __('web.cart.total') }}</span>
                    </div>

                    @forelse ($displayItems as $item)
                        <div class="grid grid-cols-[1fr_auto] py-4 border-b border-gray-100 text-sm items-start gap-4">
                            <div>
                                <p class="font-medium text-[var(--text-strong)] leading-snug">
                                    {{ $item['title'] }}
                                    <span class="text-[var(--text-faded)]">× {{ $item['quantity'] }}</span>
                                </p>
                                @if (!empty($item['subtitle']))
                                    <p class="mt-1 text-xs text-[var(--text-faded)]">{{ $item['subtitle'] }}</p>
                                @endif
                            </div>
                            <div class="whitespace-nowrap text-end">
                                @if (!empty($item['showDiscount']) && ($item['originalLineTotal'] ?? 0) > $item['lineTotal'])
                                    <p class="text-xs text-(--text-faded) line-through">
                                        {{ $item['originalLineTotal'] }} {{ $currency }}
                                    </p>
                                @endif
                                <p class="font-semibold text-(--accent-500)">
                                    {{ $item['lineTotal'] }} {{ $currency }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-sm text-(--text-faded)">{{ __('web.cart.empty') }}</div>
                    @endforelse
                </div>

                {{-- Totals --}}
                <div class="px-5 py-4 space-y-2 border-t border-gray-200">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-(--text-faded)">{{ __('web.cart.subtotal') }}</span>
                        <span class="font-semibold text-(--text-strong)">
                            {{ ($vm->totals['subtotalAmount'] ?? $displayTotal) + $itemDiscountTotal }}
                            {{ $currency }}
                        </span>
                    </div>
                    @if ($couponDiscountTotal > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-(--text-faded)">{{ __('web.cart.coupon_discount') }}</span>
                            <span class="font-semibold text-green-600">-{{ $couponDiscountTotal }}
                                {{ $currency }}</span>
                        </div>
                    @endif
                    @if ($itemDiscountTotal > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-(--text-faded)">{{ __('web.cart.discount') }}</span>
                            <span class="font-semibold text-green-600">-{{ $itemDiscountTotal }}
                                {{ $currency }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-sm font-bold border-t border-gray-200 pt-3">
                        <span class="text-(--text-strong)">{{ __('web.cart.total') }}</span>
                        <span class="text-(--accent-500)">{{ $displayTotal }} {{ $currency }}</span>
                    </div>
                </div>

                {{-- Payment methods --}}
                <div class="px-5 py-4 border-t border-gray-200 space-y-4">

                    {{-- Each payment method as its own radio --}}
                    @foreach (array_merge($onlineMethods, $bankMethods, $installmentMethods) as $value => $label)
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="radio" wire:model.live="paymentMethod" value="{{ $value }}"
                                class="h-4 w-4 accent-(--accent-500)">
                            @if (!empty($methodLogos[$value]))
                                <img src="{{ $methodLogos[$value] }}" alt="{{ $label }}"
                                    class="h-8 w-auto object-contain">
                            @else
                                <span class="text-sm font-medium text-(--text-strong)">{{ $label }}</span>
                            @endif
                        </label>
                    @endforeach

                    @if (empty($onlineMethods) && empty($bankMethods) && empty($installmentMethods))
                        <p class="text-sm text-(--text-faded)">{{ __('web.checkout.no_payment_methods') }}</p>
                    @endif

                    @error('paymentMethod')
                        <p class="text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Place order --}}
                <div class="px-5 pb-5">
                    <button wire:click="placeOrder" wire:loading.class="is-loading" wire:loading.attr="disabled"
                        class="w-full rounded bg-(--accent-500) px-4 py-3 text-sm font-bold text-white hover:brightness-95 transition disabled:opacity-60">
                        {{ __('web.checkout.confirm_order') }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

@if ($showOrderErrorModal)
    <div class="fixed inset-0 z-40 bg-[#0d0d12]/40"></div>
    <div class="fixed left-1/2 top-1/2 z-50 w-[90%] max-w-130 -translate-x-1/2 -translate-y-1/2">
        <div class="flex flex-col justify-start items-center gap-5 rounded-3xl bg-white px-6 py-8">
            <pre class="w-full rounded-xl bg-[#f7f7f7] p-4 text-xs text-[#242528]">{{ $orderErrorMessage }}</pre>
            <div class="flex flex-col items-start self-stretch gap-5">
                <p class="w-full text-center text-2xl font-bold text-[#242528]">{{ __('web.orders.thanks_title') }}
                </p>
                <p class="w-full text-center text-base text-[#100f14]">
                    <span>{{ __('web.orders.thanks_line_1') }}</span><br />
                    <span>{{ __('web.orders.thanks_line_2') }}</span>
                </p>
            </div>
            <div class="flex flex-col items-center self-stretch gap-4">
                <button type="button" wire:click="closeOrderErrorModal"
                    class="flex h-12 w-full items-center justify-center gap-2.5 rounded-[100px] bg-[#99745c] px-3 py-2.5">
                    <span class="text-sm font-semibold text-white">{{ __('web.orders.view_details') }}</span>
                </button>
                <a href="{{ route('home') }}"
                    class="flex h-10.5 w-full items-center justify-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold text-[#242528]">
                    {{ __('web.common.back_to_home') }}
                </a>
            </div>
        </div>
    </div>
@endif


