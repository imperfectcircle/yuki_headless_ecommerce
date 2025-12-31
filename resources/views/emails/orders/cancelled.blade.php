@component('mail::message')
# Your order has been cancelled.

Hi {{ $order->customer_full_name ?? 'there' }},

Your order **#{{ $order->number }}** has been cancelled.

## Order Summary

@component('mail::table')
| Product | Quantity | Price |
|---------|----------|-------|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ number_format($item->total / 100, 2) }} {{ $order->currency }} |
@endforeach
@endcomponent

---

**Total:** **{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}**

---

If you have any questions about your order, just reply to this email.

Thanks you!

{{ config('app.name') }}
@endcomponent