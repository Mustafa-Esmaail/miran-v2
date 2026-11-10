<div class="bg-brand-dark text-white">
    <div class="mx-auto max-w-[1320px] px-2 lg:py-3 py-1 sm:px-3 lg:px-8">
        <div @class([
            'flex  items-center justify-center lg:gap-5
                    gap-1 text-xs font-medium',
            'flex-row-reverse' => $isRtl,
        ])>
            <span class="hidden text-white lg:inline">{{ $leftText }}</span>

            <div class="  text-white">
                {{ $centerText }}
            </div>

            <span class="hidden text-white lg:inline">{{ $rightText }}</span>
        </div>
    </div>
</div>
