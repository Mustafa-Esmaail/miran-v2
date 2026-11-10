<x-account-shell active="certificate-form" :title="__('web.certificate_form.title')">
    <div class="flex flex-col gap-4">
        <div class="rounded-[20px] border border-[#ebebeb] bg-white p-6">
            <p class="text-lg font-semibold text-[#30373d]">{{ __('web.certificate_form.heading') }}</p>
            <p class="mt-1 text-sm text-[#818181]">{{ __('web.certificate_form.description') }}</p>

            @if ($submitted)
                <div class="mt-6 flex items-center gap-3 rounded-xl bg-[#e7f4ed] px-4 py-3 text-sm font-medium text-[#1f7a4c]">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 shrink-0" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10 10-4.49 10-10S17.51 2 12 2Zm4.78 7.7-5.67 5.67a.75.75 0 0 1-1.06 0l-2.83-2.83a.754.754 0 0 1 0-1.06c.29-.29.77-.29 1.06 0l2.3 2.3 5.14-5.14c.29-.29.77-.29 1.06 0 .29.29.29.77 0 1.06Z" />
                    </svg>
                    <span>{{ __('web.certificate_form.saved') }}</span>
                </div>
            @elseif ($isRejected)
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-red-500" fill="currentColor" aria-hidden="true">
                        <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm-.75 5.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm.75 9.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">{{ __('web.certificate_form.rejected_title') }}</p>
                        <p class="mt-0.5 text-red-700">{{ __('web.certificate_form.rejected_body') }}</p>
                    </div>
                </div>
            @endif

            <form wire:submit="submit" class="mt-6 flex flex-col gap-6">

                {{-- Course group selector --}}
                @if ($groups->isNotEmpty())
                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.course_group') }}</label>
                        <select wire:model.live="courseGroupId"
                            class="w-full rounded-xl border border-[#ebebeb] bg-white px-4 py-2.5 text-sm text-[#30373d] focus:outline-none focus:ring-2 focus:ring-[#99745c]/30">
                            @foreach ($groups as $group)
                                <option value="{{ $group['id'] }}">{{ $group['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @php
                    $inputBase = 'w-full rounded-xl border px-4 py-2.5 text-sm text-[#30373d] focus:outline-none focus:ring-2 focus:ring-[#99745c]/30';
                @endphp

                {{-- Arabic names --}}
                <div>
                    <p class="mb-3 text-sm font-semibold text-[#30373d]">{{ __('web.certificate_form.section_ar') }}</p>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['field' => 'firstNameAr',  'label' => __('web.certificate_form.first_name_ar'),  'required' => true],
                            ['field' => 'secondNameAr', 'label' => __('web.certificate_form.second_name_ar'), 'required' => true],
                            ['field' => 'thirdNameAr',  'label' => __('web.certificate_form.third_name_ar'),  'required' => true],
                            ['field' => 'lastNameAr',   'label' => __('web.certificate_form.last_name_ar'),   'required' => true],
                        ] as $f)
                            <div class="grid gap-1.5">
                                <label class="text-sm font-medium text-[#686868]">
                                    {{ $f['label'] }}@if($f['required']) <span class="text-red-500">*</span>@endif
                                </label>
                                <input wire:model="{{ $f['field'] }}" type="text"
                                    @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has($f['field']), 'border-[#ebebeb] bg-white' => !$errors->has($f['field'])])>
                                @error($f['field']) <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- English names --}}
                <div>
                    <p class="mb-3 text-sm font-semibold text-[#30373d]">{{ __('web.certificate_form.section_en') }}</p>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            ['field' => 'firstNameEn',  'label' => __('web.certificate_form.first_name_en'),  'required' => true],
                            ['field' => 'secondNameEn', 'label' => __('web.certificate_form.second_name_en'), 'required' => true],
                            ['field' => 'thirdNameEn',  'label' => __('web.certificate_form.third_name_en'),  'required' => true],
                            ['field' => 'lastNameEn',   'label' => __('web.certificate_form.last_name_en'),   'required' => true],
                        ] as $f)
                            <div class="grid gap-1.5">
                                <label class="text-sm font-medium text-[#686868]">
                                    {{ $f['label'] }}@if($f['required']) <span class="text-red-500">*</span>@endif
                                </label>
                                <input wire:model="{{ $f['field'] }}" type="text"
                                    @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has($f['field']), 'border-[#ebebeb] bg-white' => !$errors->has($f['field'])])>
                                @error($f['field']) <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Personal info --}}
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.id_number') }} <span class="text-red-500">*</span></label>
                        <input wire:model="idNumber" type="text"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('idNumber'), 'border-[#ebebeb] bg-white' => !$errors->has('idNumber')])>
                        @error('idNumber') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.birth_date') }} <span class="text-red-500">*</span></label>
                        <input wire:model="birthDate" type="date"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('birthDate'), 'border-[#ebebeb] bg-white' => !$errors->has('birthDate')])>
                        @error('birthDate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.gender') }} <span class="text-red-500">*</span></label>
                        <select wire:model="gender"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('gender'), 'border-[#ebebeb] bg-white' => !$errors->has('gender')])>
                            <option value="">—</option>
                            <option value="male">{{ __('web.certificate_form.male') }}</option>
                            <option value="female">{{ __('web.certificate_form.female') }}</option>
                        </select>
                        @error('gender') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.nationality') }} <span class="text-red-500">*</span></label>
                        <select wire:model="nationality"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('nationality'), 'border-[#ebebeb] bg-white' => !$errors->has('nationality')])>
                            <option value="">—</option>
                            @foreach ($countries as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('nationality') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.qualification') }} <span class="text-red-500">*</span></label>
                        <select wire:model="qualification"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('qualification'), 'border-[#ebebeb] bg-white' => !$errors->has('qualification')])>
                            <option value="">—</option>
                            @foreach ($qualifications as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('qualification') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.email') }} <span class="text-red-500">*</span></label>
                        <input wire:model="email" type="email"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('email'), 'border-[#ebebeb] bg-white' => !$errors->has('email')])>
                        @error('email') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label class="text-sm font-medium text-[#686868]">{{ __('web.certificate_form.phone') }} <span class="text-red-500">*</span></label>
                        <input wire:model="phone" type="text"
                            @class([$inputBase, 'border-red-400 bg-red-50' => $errors->has('phone'), 'border-[#ebebeb] bg-white' => !$errors->has('phone')])>
                        @error('phone') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center rounded-xl px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 {{ $isRejected ? 'bg-red-600 hover:bg-red-700' : 'bg-[#99745c]' }}">
                        {{ $isRejected ? __('web.certificate_form.resubmit') : __('web.certificate_form.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-account-shell>
