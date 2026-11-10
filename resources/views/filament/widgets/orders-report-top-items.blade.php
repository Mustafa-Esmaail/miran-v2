<x-filament-widgets::widget>
    <x-filament::section :heading="$this->getHeading()">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start text-xs font-semibold text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2">{{ __('admin.labels.item') }}</th>
                        <th class="px-3 py-2">{{ __('admin.labels.item_type') }}</th>
                        <th class="px-3 py-2">{{ __('admin.labels.quantity') }}</th>
                        <th class="px-3 py-2">{{ __('admin.labels.orders') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getTopItems() as $item)
                        <tr class="border-t border-gray-100 dark:border-white/5">
                            <td class="px-3 py-2 font-medium">{{ $item['title'] }}</td>
                            <td class="px-3 py-2">{{ $item['type'] }}</td>
                            <td class="px-3 py-2">{{ $item['quantity'] }}</td>
                            <td class="px-3 py-2">{{ $item['orders_count'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                {{ __('admin.empty_states.orders.heading') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
