<div class="space-y-2">
    <p>Payment: {{ $payment->payment_number }} ({{ $payment->currency }})</p>
    <p>Amount: {{ $payment->amount }}</p>
    <p>Allocated: {{ $payment->allocated_amount }}</p>
    <p>Unallocated: {{ $payment->unallocated_amount }}</p>
    <p>Allocations are final. Each invoice may appear once per payment.</p>
</div>
