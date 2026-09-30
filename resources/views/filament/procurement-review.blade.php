<div class="space-y-4">
    <p><strong>{{ $record->supplier->name }}</strong></p>
    @if ($kind === 'payment')
        <p>{{ $record->payment_number }}: {{ $record->currency }} {{ $record->amount }}</p>
        <p>Method: {{ str_replace('_', ' ', $record->payment_method->value) }}</p>
        <p>Reference: {{ $record->reference ?? '-' }}</p>
    @else
        @if ($kind === 'receipt')
            <p>{{ $record->goods_receipt_number }} / {{ $record->purchaseOrder->purchase_order_number }}</p>
            <p>Destination: {{ $record->inventoryLocation->name }}</p>
        @else
            <p>{{ $record->purchase_order_number }} / Total: {{ $record->total_amount }}</p>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead><tr>
                    <th class="p-2">Item</th>
                    @if ($kind === 'receipt')
                        <th class="p-2">Ordered</th><th class="p-2">Previously accepted</th><th class="p-2">Remaining</th>
                        <th class="p-2">Received now</th><th class="p-2">Accepted</th><th class="p-2">Rejected</th>
                    @else
                        <th class="p-2">Quantity</th>
                    @endif
                    <th class="p-2">Unit cost</th>
                    <th class="p-2">{{ $kind === 'receipt' ? 'Rejection reason' : 'Line total' }}</th>
                </tr></thead>
                <tbody>
                @foreach ($record->items as $item)
                    <tr>
                    <td class="p-2">{{ $kind === 'receipt' ? $item->inventoryItem->name : $item->item_name }}</td>
                    @if ($kind === 'receipt')
                        <td class="p-2">{{ $item->purchaseOrderItem->quantity_ordered }}</td>
                        <td class="p-2">{{ $item->purchaseOrderItem->quantity_received }}</td>
                        <td class="p-2">{{ $item->purchaseOrderItem->remaining_quantity }}</td>
                        <td class="p-2">{{ $item->quantity_received }}</td>
                        <td class="p-2">{{ $item->quantity_accepted }}</td>
                        <td class="p-2">{{ $item->quantity_rejected }}</td>
                    @else
                        <td class="p-2">{{ $item->quantity_ordered }}</td>
                    @endif
                    <td class="p-2">{{ $item->unit_cost }}</td>
                    <td class="p-2">{{ $kind === 'receipt' ? $item->rejection_reason : $item->line_total }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if ($record->notes)<p>{{ $record->notes }}</p>@endif
</div>
