<x-account-shell active="delete-account" :title="__('web.account.delete_title')" :subtitle="__('web.account.delete_subtitle')">
    <div class="rounded-[20px] border border-[#ebebeb] bg-white p-6">
        <p class="text-lg text-[#30373d]">
            {{ __('web.account.delete_body') }}
        </p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <button
                type="button"
                wire:click="confirmDelete"
                class="inline-flex items-center justify-center rounded-[50px] bg-[#f74b59] px-6 py-3 text-sm font-bold text-white transition hover:brightness-95"
            >
                {{ __('web.account.delete_cta') }}
            </button>
            <a
                href="{{ route('dashboard') }}"
                class="inline-flex items-center justify-center rounded-[50px] border border-[#e1e1e1] px-6 py-3 text-sm font-bold text-[#686868] transition hover:bg-[#f7f7f7]"
            >
                {{ __('web.account.keep_account') }}
            </a>
        </div>
    </div>

    @if ($confirming)
        <div class="fixed inset-0 z-40 bg-[#0d0d12]/40"></div>
        <div class="fixed left-1/2 top-1/2 z-50 w-[90%] max-w-[480px] -translate-x-1/2 -translate-y-1/2">
            <div
                role="dialog"
                aria-modal="true"
                class="flex flex-col-reverse items-center gap-8 overflow-hidden rounded-[20px] bg-white p-6"
            >
                <div class="flex w-full gap-1.5">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="flex-1 rounded-[50px] border border-[#e1e1e1] px-4 py-3.5 text-sm font-bold text-[#686868]"
                    >
                        {{ __('web.account.delete_cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="deleteAccount"
                        wire:loading.attr="disabled"
                        class="flex-1 rounded-[50px] bg-[#f74b59] px-4 py-3.5 text-sm font-bold text-white transition hover:brightness-95"
                    >
                        {{ __('web.account.delete_action') }}
                    </button>
                </div>

                <div class="flex flex-col items-center gap-4">
                    <div class="rounded-full bg-[#ffede9] p-4">
                        <svg viewBox="0 0 32 32" class="h-8 w-8 text-[#f74b59]" fill="currentColor" aria-hidden="true">
                            <path d="M22.4 2.66666H18.9333C14.6667 2.66666 12 5.33332 12 9.59999V15H17.92L15.16 12.24C14.96 12.04 14.8667 11.7867 14.8667 11.5333C14.8667 11.28 14.96 11.0267 15.16 10.8267C15.5467 10.44 16.1867 10.44 16.5733 10.8267L21.04 15.2933C21.4267 15.68 21.4267 16.32 21.04 16.7067L16.5733 21.1733C16.1867 21.56 15.5467 21.56 15.16 21.1733C14.7733 20.7867 14.7733 20.1467 15.16 19.76L17.92 17H12V22.4C12 26.6667 14.6667 29.3333 18.9333 29.3333H22.3867C26.6533 29.3333 29.32 26.6667 29.32 22.4V9.59999C29.3333 5.33332 26.6667 2.66666 22.4 2.66666Z"></path>
                            <path d="M3.66797 15C3.1213 15 2.66797 15.4533 2.66797 16C2.66797 16.5467 3.1213 17 3.66797 17H12.0013V15H3.66797Z"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-semibold text-[#242528]">{{ __('web.account.delete_confirm_title') }}</p>
                        <p class="mt-2 text-base text-[#686868]">{{ __('web.account.delete_confirm_body') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-account-shell>
