<x-filament-widgets::widget>
    <style>
        .widget-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .widget-table th, 
        .widget-table td {
            padding: 0.75rem 1rem !important;
        }
        .widget-table th {
            font-weight: 600;
            background-color: rgba(39, 39, 42, 0.6);
            color: #d1d5db;
            border-bottom: 1px solid #3f3f46;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .widget-table td {
            border-bottom: 1px solid #27272a;
            color: #e4e4e7;
        }
        .widget-table tbody tr:hover {
            background-color: rgba(39, 39, 42, 0.4);
        }
        .text-right {
            text-align: right !important;
        }
    </style>
    <x-filament::section>
        <x-slot name="heading">
            Top Selling Items
        </x-slot>

        @if($items->isEmpty())
            <p class="text-gray-500">No sales recorded during this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="widget-table w-full text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="px-4 py-2">#</th>
                            <th class="px-4 py-2 font-bold">Item</th>
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2 text-right">Units Sold</th>
                            <th class="px-4 py-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $index => $item)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $index + 1 }}</td>
                                <td class="px-4 py-2 font-semibold">{{ $item->item_name }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $item->category_name ?? 'Uncategorized' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($item->total_qty) }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ number_format($item->total_revenue, 0, ',', '.') }} FCFA</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
