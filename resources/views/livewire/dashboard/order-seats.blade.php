<div class="rounded-2xl border border-[#ebebeb] bg-white p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-base font-semibold text-[#100f14]">{{ __('web.checkout.assign_seats') }}</p>
            <p class="text-xs text-[#818181]">{{ __('web.checkout.assign_seats_hint') }}</p>
        </div>
        <span class="rounded-full bg-[#99745c]/10 px-3 py-1 text-xs font-semibold text-[#99745c]">
            {{ __('web.checkout.order_status_label', ['status' => $orderStatusLabel]) }}
        </span>
    </div>

    @if ($message)
        <div class="mt-4 rounded-xl bg-[#f8fafb] px-4 py-2 text-xs text-[#4f4f4f]">{{ $message }}</div>
    @endif

    @if ($error)
        <div class="mt-4 rounded-xl bg-[#fdf2f2] px-4 py-2 text-xs text-[#b42318]">{{ $error }}</div>
    @endif

    <div class="mt-6 flex flex-col gap-4">
        @foreach ($items as $item)
            <div class="rounded-xl border border-[#ebebeb] px-4 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-[#100f14]">{{ $item['title'] }}</p>
                        <p class="mt-1 text-xs text-[#818181]">
                            {{ __('web.checkout.seat_summary', ['assigned' => $item['assigned_count'], 'available' => $item['available_count']]) }}
                        </p>
                    </div>

                    @if ($orderStatus === 'pending')
                        <div class="flex flex-wrap items-center gap-2">
                            <input
                                type="number"
                                min="1"
                                wire:model.live="quantityUpdates.{{ $item['id'] }}"
                                class="h-9 w-20 rounded-full border border-[#ebebeb] px-3 text-center text-sm font-semibold text-[#100f14]"
                            >
                            <button
                                type="button"
                                wire:click="updateQuantity({{ $item['id'] }})"
                                class="rounded-full border border-[#99745c] px-4 py-2 text-xs font-semibold text-[#99745c]"
                            >
                                {{ __('web.checkout.update_quantity') }}
                            </button>
                        </div>
                    @endif
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    @foreach ($item['seats'] as $seat)
                        <div class="rounded-lg bg-[#f8fafb] px-3 py-2" wire:key="order-seat-{{ $seat['id'] }}">
                            @if (! empty($editingSeats[$seat['id']]))
                                {{-- Inline edit form --}}
                                <div class="flex flex-col gap-2">
                                    @php
                                        $editPhoneKey = 'editForm.'.$seat['id'].'.phone';
                                        $editNameKey  = 'editForm.'.$seat['id'].'.name';
                                        $editEmailKey = 'editForm.'.$seat['id'].'.email';
                                    @endphp
                                    <div class="grid gap-2 sm:grid-cols-3">
                                        <div class="flex flex-col gap-0.5">
                                            <input
                                                type="tel"
                                                wire:model.live="editForm.{{ $seat['id'] }}.phone"
                                                placeholder="{{ __('web.checkout.invite_phone_placeholder') }}"
                                                class="h-9 rounded-full border px-3 text-xs font-semibold text-[#100f14] {{ $errors->has($editPhoneKey) ? 'border-red-400' : 'border-[#ebebeb]' }}"
                                            >
                                            @error($editPhoneKey)<p class="px-3 text-[10px] text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <input
                                                type="text"
                                                wire:model.live="editForm.{{ $seat['id'] }}.name"
                                                placeholder="{{ __('web.checkout.invite_name_placeholder') }}"
                                                class="h-9 rounded-full border px-3 text-xs font-semibold text-[#100f14] {{ $errors->has($editNameKey) ? 'border-red-400' : 'border-[#ebebeb]' }}"
                                            >
                                            @error($editNameKey)<p class="px-3 text-[10px] text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <input
                                                type="email"
                                                wire:model.live="editForm.{{ $seat['id'] }}.email"
                                                placeholder="{{ __('web.checkout.invite_email_placeholder') }}"
                                                class="h-9 rounded-full border px-3 text-xs font-semibold text-[#100f14] {{ $errors->has($editEmailKey) ? 'border-red-400' : 'border-[#ebebeb]' }}"
                                            >
                                            @error($editEmailKey)<p class="px-3 text-[10px] text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="updateSeat({{ $seat['id'] }})"
                                            wire:loading.attr="disabled"
                                            class="rounded-full bg-[#99745c] px-4 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                                        >
                                            {{ __('web.checkout.save_invite') }}
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="cancelEditSeat({{ $seat['id'] }})"
                                            class="rounded-full border border-[#ebebeb] px-4 py-1.5 text-xs font-semibold text-[#4f4f4f]"
                                        >
                                            {{ __('web.common.cancel') }}
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- Normal display row --}}
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="flex flex-col gap-0.5">
                                        @if ($seat['status'] === 'unassigned')
                                            <span class="text-xs text-[#818181]">{{ __('web.checkout.unassigned') }}</span>
                                        @else
                                            @if (filled($seat['invited_email']))
                                                <span class="text-xs font-semibold text-[#100f14]">{{ $seat['invited_email'] }}</span>
                                            @endif
                                            @if (filled($seat['invited_name']) && ! $seat['is_purchaser'])
                                                <span class="text-xs text-[#4f4f4f]">{{ $seat['invited_name'] }}</span>
                                            @endif
                                            @if (filled($seat['phone']))
                                                <span class="text-xs text-[#818181]">{{ $seat['phone'] }}</span>
                                            @endif
                                            @if (! filled($seat['invited_email']) && ! filled($seat['phone']))
                                                <span class="text-xs text-[#818181]">{{ $seat['invited_name'] ?? __('web.checkout.unassigned') }}</span>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-xs font-semibold text-[#99745c]">
                                        <span>{{ __('web.checkout.seat_status', ['status' => $seat['status_label']]) }}</span>
                                        @if ($orderStatus === 'pending' && $seat['status'] === 'invited' && empty($seat['is_purchaser']))
                                            <button
                                                type="button"
                                                wire:click="startEditSeat({{ $seat['id'] }})"
                                                class="rounded-full border border-[#99745c] px-3 py-1 text-xs font-semibold text-[#99745c]"
                                            >
                                                {{ __('web.checkout.edit_invite') }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="removeInvite({{ $seat['id'] }})"
                                                class="rounded-full border border-[#b42318] px-3 py-1 text-xs font-semibold text-[#b42318]"
                                            >
                                                {{ __('web.checkout.remove_phone') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($item['quantity'] > 1)
                    <div class="mt-4 flex flex-col gap-3">
                        <div class="grid gap-3 sm:grid-cols-3">
                            @php
                                $phoneKey = 'inviteForm.'.$item['id'].'.phone';
                                $nameKey  = 'inviteForm.'.$item['id'].'.name';
                                $emailKey = 'inviteForm.'.$item['id'].'.email';
                            @endphp
                            <div class="flex flex-col gap-1">
                                <input
                                    type="tel"
                                    wire:model.live="inviteForm.{{ $item['id'] }}.phone"
                                    placeholder="{{ __('web.checkout.invite_phone_placeholder') }}"
                                    class="h-10 rounded-full border px-4 text-xs font-semibold text-[#100f14] {{ $errors->has($phoneKey) ? 'border-red-400' : 'border-[#ebebeb]' }}"
                                >
                                @error($phoneKey)
                                    <p class="px-4 text-[10px] text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <input
                                    type="text"
                                    wire:model.live="inviteForm.{{ $item['id'] }}.name"
                                    placeholder="{{ __('web.checkout.invite_name_placeholder') }}"
                                    class="h-10 rounded-full border px-4 text-xs font-semibold text-[#100f14] {{ $errors->has($nameKey) ? 'border-red-400' : 'border-[#ebebeb]' }}"
                                >
                                @error($nameKey)
                                    <p class="px-4 text-[10px] text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex flex-col gap-1">
                                <input
                                    type="email"
                                    wire:model.live="inviteForm.{{ $item['id'] }}.email"
                                    placeholder="{{ __('web.checkout.invite_email_placeholder') }}"
                                    class="h-10 rounded-full border px-4 text-xs font-semibold text-[#100f14] {{ $errors->has($emailKey) ? 'border-red-400' : 'border-[#ebebeb]' }}"
                                >
                                @error($emailKey)
                                    <p class="px-4 text-[10px] text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="assignSeat({{ $item['id'] }})"
                            wire:loading.attr="disabled"
                            class="w-fit rounded-full bg-[#99745c] px-4 py-2 text-xs font-semibold text-white disabled:opacity-60"
                        >
                            {{ __('web.checkout.add_invitee') }}
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
