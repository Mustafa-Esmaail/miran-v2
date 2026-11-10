<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            {{ __('admin.settings.save') }}
        </x-filament::button>
    </form>
</x-filament-panels::page>
