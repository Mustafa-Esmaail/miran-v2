<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit">
                {{ __('admin.settings.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
