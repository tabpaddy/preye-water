<div class="space-y-4">
    <p><strong>{{ $adjustment->adjustment_number }}</strong> - {{ $adjustment->inventoryLocation->name }}</p>
    <p>Reason: {{ str_replace('_', ' ', $adjustment->reason->value) }} - Created by {{ $adjustment->creator->staff_number }}</p>
    @if ($adjustment->notes)
        <p>{{ $adjustment->notes }}</p>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr>
                    <th class="p-2">Item</th>
                    <th class="p-2">Unit</th>
                    <th class="p-2">System count</th>
                    <th class="p-2">Physical count</th>
                    <th class="p-2">Difference</th>
                    <th class="p-2">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($adjustment->items as $item)
                    <tr>
                        <td class="p-2">{{ $item->inventoryItem->sku }} - {{ $item->inventoryItem->name }}</td>
                        <td class="p-2">{{ $item->inventoryItem->unit_of_measure->value }}</td>
                        <td class="p-2">{{ $item->system_quantity }}</td>
                        <td class="p-2">{{ $item->counted_quantity }}</td>
                        <td class="p-2">{{ $item->difference_quantity }}</td>
                        <td class="p-2">{{ $item->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
