<x-filament-widgets::widget>
    <x-filament::card>
        <h2 class="text-lg font-bold mb-4">Stock Summary</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Total SKUs</p>
                <p class="text-2xl font-bold">{{ \App\Models\Item::count() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Units on Hand</p>
                <p class="text-2xl font-bold">{{ \App\Models\ItemStockLevel::sum('qty_on_hand') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Stock Value</p>
                <p class="text-2xl font-bold">0</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Items Below Reorder</p>
                <p class="text-2xl font-bold text-red-500">0</p>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>
