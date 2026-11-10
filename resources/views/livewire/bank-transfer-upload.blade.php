<div class="app-container py-10">
    <h1 class="text-2xl font-semibold text-[var(--text-strong)]">{{ __('web.bank_transfer.title') }}</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_1fr]">
        <div class="rounded-2xl border border-[var(--panel-border)] bg-white p-6">
            <div class="text-sm text-[var(--text-subtle)]">{{ __('web.bank_transfer.bank') }}</div>
            <div class="mt-1 text-base font-semibold text-[var(--text-strong)]">{{ $bank['bank_name'] }}</div>

            <div class="mt-4 text-sm text-[var(--text-subtle)]">{{ __('web.bank_transfer.account') }}</div>
            <div class="mt-1 text-base font-semibold text-[var(--text-strong)]">{{ $bank['account_number'] }}</div>

            <div class="mt-4 text-sm text-[var(--text-subtle)]">{{ __('web.bank_transfer.iban') }}</div>
            <div class="mt-1 text-base font-semibold text-[var(--text-strong)]">{{ $bank['iban'] }}</div>
        </div>

        <div class="rounded-2xl border border-[var(--panel-border)] bg-white p-6">
            <h2 class="text-lg font-semibold text-[var(--text-strong)]">{{ __('web.bank_transfer.upload_receipt') }}</h2>

            <form wire:submit.prevent="submit">
                <div class="mt-4" x-data="{ fileName: '' }">
                    <input
                        id="receipt-upload"
                        type="file"
                        wire:model="receipt"
                        class="sr-only"
                        x-on:change="fileName = $event.target.files[0]?.name || ''"
                    >
                    <label
                        for="receipt-upload"
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-[var(--accent-500)] bg-[var(--accent-10)] px-4 py-8 text-sm text-[var(--text-subtle)]"
                    >
                        <span class="text-base font-semibold text-[var(--accent-500)]">
                            {{ __('web.bank_transfer.upload_receipt') }}
                        </span>
                    </label>
                    <div class="mt-3 text-xs text-[var(--text-faded)]" x-show="fileName" x-text="fileName"></div>
                    @error('receipt')<div class="mt-2 text-xs text-alert">{{ $message }}</div>@enderror
                </div>

                <button
                    type="submit"
                    class="btn-primary btn-focus mt-5 w-full rounded-full px-5 py-3 text-sm font-semibold"
                    wire:loading.class="is-loading"
                    wire:loading.attr="disabled"
                    wire:target="receipt,submit"
                >
                    {{ __('web.bank_transfer.upload_receipt') }}
                </button>
            </form>
        </div>
    </div>
</div>
