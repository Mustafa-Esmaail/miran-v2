<x-account-shell active="profile" :title="__('web.profile.edit_profile')">
    <div class="flex flex-col gap-4">
        <div class="rounded-[20px] border border-[#ebebeb] bg-white p-6">
            <p class="text-lg font-semibold text-[#30373d]">{{ __('web.profile.info_title') }}</p>

            @if (session('status'))
                <div class="mt-4 rounded-lg border border-[#ebebeb] bg-[#f8fafb] px-3 py-2 text-sm text-[#4f4f4f]">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('avatar_path'))
                <div class="mt-2 rounded-lg border border-[#ebebeb] bg-[#f8fafb] px-3 py-2 text-xs text-[#4f4f4f]">
                    {{ __('web.profile.avatar_path') }}: {{ session('avatar_path') }}
                </div>
            @endif

            <div class="mt-6 grid gap-4">
                <div class="grid gap-1.5">
                    <p class="text-sm font-medium text-[#686868]">{{ __('web.profile.avatar') }}</p>
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 overflow-hidden rounded-full bg-[#374746]">
                            @if ($avatar)
                                <img src="{{ $avatar->temporaryUrl() }}"
                                    alt="{{ __('web.profile.avatar_preview_alt') }}" class="h-full w-full object-cover">
                            @elseif (auth('web')->user()?->avatar_path)
                                <img src="{{ \App\Support\S3Url::temporary(auth('web')->user()->avatar_path) }}"
                                    alt="{{ __('web.profile.avatar_alt') }}" class="h-full w-full object-cover">
                            @else
                                <div
                                    class="flex h-full w-full items-center justify-center text-sm font-semibold text-white">
                                    {{ auth('web')->user() ? strtoupper(\Illuminate\Support\Str::substr(auth('web')->user()->name ?? 'U', 0, 1)) : 'U' }}
                                </div>
                            @endif
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm font-bold text-[#3f619e]">
                            <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" aria-hidden="true">
                                <path d="M5 10H15" stroke="#3F619E" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round"></path>
                                <path d="M10 15V5" stroke="#3F619E" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round"></path>
                            </svg>
                            <span>{{ __('web.profile.change_avatar') }}</span>
                            <input type="file" class="hidden" accept="image/*" wire:model="avatar">
                        </label>
                    </div>
                    @error('avatar')
                        <div class="text-xs text-alert">{{ $message }}</div>
                    @enderror
                </div>

                <div class="grid gap-4">
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.name') }}</label>
                        <input class="w-full rounded-full border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            wire:model.defer="name">
                        @error('name')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.email') }}</label>
                        <input type="email"
                            class="w-full rounded-full border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            wire:model.defer="email">
                        @error('email')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.phone') }}</label>
                        <input type="tel"
                            class="w-full rounded-full border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            placeholder="{{ __('web.profile.phone_placeholder') }}" wire:model.defer="phone">
                        @error('phone')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.national_id') }}</label>
                        <input type="text"
                            class="w-full rounded-full border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            placeholder="{{ __('web.profile.national_id_placeholder') }}"
                            wire:model.defer="national_id">
                        @error('national_id')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.nationality') }}</label>
                        <input type="text"
                            class="w-full rounded-full border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            placeholder="{{ __('web.profile.nationality_placeholder') }}"
                            wire:model.defer="nationality">
                        @error('nationality')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">
                            {{ __('web.profile.birth_date') }}<span class="text-[#f74b59]">*</span>
                        </label>
                        <div class="flex items-center gap-2 rounded-full border border-[#ebebeb] px-4 py-3">
                            <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" aria-hidden="true">
                                <path
                                    d="M5.78292 1.66669C6.12947 1.66669 6.4104 1.93686 6.4104 2.27014V3.48741C6.96699 3.47703 7.59087 3.47703 8.29284 3.47703H11.6394C12.3414 3.47703 12.9653 3.47703 13.5219 3.48741V2.27014C13.5219 1.93686 13.8028 1.66669 14.1493 1.66669C14.4959 1.66669 14.7768 1.93686 14.7768 2.27014V3.5408C15.981 3.63354 16.7716 3.86113 17.3524 4.41968C17.9332 4.97823 18.1698 5.73849 18.2662 6.89657L18.3327 7.50002H1.66602L1.66643 6.89657C1.76244 5.73849 1.9991 4.97823 2.57989 4.41968C3.16069 3.86113 3.95123 3.63354 5.15544 3.5408V2.27014C5.15544 1.93686 5.43637 1.66669 5.78292 1.66669Z"
                                    fill="#8C91A2" />
                                <path opacity="0.5"
                                    d="M18.3331 11.6667V10C18.3331 9.30081 18.3304 8.05433 18.3196 7.49994H1.67447C1.66372 8.05433 1.66643 9.30081 1.66643 10V11.6667C1.66643 14.8094 1.66643 16.3807 2.64274 17.357C3.61906 18.3333 5.1904 18.3333 8.3331 18.3333H11.6664C14.8091 18.3333 16.3805 18.3333 17.3568 17.357C18.3331 16.3807 18.3331 14.8094 18.3331 11.6667Z"
                                    fill="#8C91A2" />
                                <path
                                    d="M15.0007 14.1667C15.0007 14.6269 14.6276 15 14.1673 15C13.7071 15 13.334 14.6269 13.334 14.1667C13.334 13.7064 13.7071 13.3333 14.1673 13.3333C14.6276 13.3333 15.0007 13.7064 15.0007 14.1667Z"
                                    fill="#8C91A2" />
                                <path
                                    d="M15.0007 10.8333C15.0007 11.2936 14.6276 11.6667 14.1673 11.6667C13.7071 11.6667 13.334 11.2936 13.334 10.8333C13.334 10.3731 13.7071 10 14.1673 10C14.6276 10 15.0007 10.3731 15.0007 10.8333Z"
                                    fill="#8C91A2" />
                                <path
                                    d="M10.8327 14.1667C10.8327 14.6269 10.4596 15 9.99935 15C9.53911 15 9.16602 14.6269 9.16602 14.1667C9.16602 13.7064 9.53911 13.3333 9.99935 13.3333C10.4596 13.3333 10.8327 13.7064 10.8327 14.1667Z"
                                    fill="#8C91A2" />
                                <path
                                    d="M10.8327 10.8333C10.8327 11.2936 10.4596 11.6667 9.99935 11.6667C9.53911 11.6667 9.16602 11.2936 9.16602 10.8333C9.16602 10.3731 9.53911 10 9.99935 10C10.4596 10 10.8327 10.3731 10.8327 10.8333Z"
                                    fill="#8C91A2" />
                                <path
                                    d="M6.66667 14.1667C6.66667 14.6269 6.29357 15 5.83333 15C5.3731 15 5 14.6269 5 14.1667C5 13.7064 5.3731 13.3333 5.83333 13.3333C6.29357 13.3333 6.66667 13.7064 6.66667 14.1667Z"
                                    fill="#8C91A2" />
                                <path
                                    d="M6.66667 10.8333C6.66667 11.2936 6.29357 11.6667 5.83333 11.6667C5.3731 11.6667 5 11.2936 5 10.8333C5 10.3731 5.3731 10 5.83333 10C6.29357 10 6.66667 10.3731 6.66667 10.8333Z"
                                    fill="#8C91A2" />
                            </svg>
                            <input type="date" class="w-full bg-transparent text-sm text-[#30373d] outline-none"
                                wire:model.defer="birth_date">
                        </div>
                        @error('birth_date')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">
                            {{ __('web.profile.gender') }}<span class="text-[#f74b59]">*</span>
                        </label>
                        <div
                            class="flex items-center justify-between gap-2 rounded-full border border-[#ebebeb] px-4 py-3">
                            <select class="w-full bg-transparent text-sm text-[#30373d] outline-none"
                                wire:model.defer="gender">
                                <option value="">{{ __('web.profile.gender_placeholder') }}</option>
                                <option value="male">{{ __('web.profile.gender_male') }}</option>
                                <option value="female">{{ __('web.profile.gender_female') }}</option>
                            </select>
                            <svg viewBox="0 0 20 20" class="h-5 w-5" fill="none" aria-hidden="true">
                                <path d="M15.8327 7.5L9.99935 12.5L4.16602 7.5" stroke="#868686" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        @error('gender')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.about') }}</label>
                        <textarea class="min-h-[140px] w-full rounded-xl border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            placeholder="{{ __('web.profile.about_placeholder') }}" wire:model.defer="about"></textarea>
                        @error('about')
                            <div class="text-xs text-alert">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.profile.language') }}</label>
                        <select
                            class="w-full rounded-full border border-[#ebebeb] px-4 py-3 text-sm text-[#30373d]"
                            wire:model.defer="locale"
                        >
                            <option value="">{{ __('web.profile.language_default') }}</option>
                            <option value="en">{{ __('web.nav.lang_en') }}</option>
                            <option value="ar">{{ __('web.nav.lang_ar') }}</option>
                        </select>
                        @error('locale')<div class="text-xs text-alert">{{ $message }}</div>@enderror
                    </div> --}}
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button class="rounded-full bg-[#ebe8e6] px-6 py-3 text-sm font-bold text-[#99745c]" wire:click="save"
                wire:loading.class="is-loading" wire:loading.attr="disabled">
                {{ __('web.profile.save') }}
            </button>
            <a class="text-sm text-link hover:text-linkHover" href="{{ route('password.request') }}">
                {{ __('web.profile.change_password') }}
            </a>
        </div>
    </div>
</x-account-shell>
