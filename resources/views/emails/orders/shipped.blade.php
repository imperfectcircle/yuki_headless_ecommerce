@component('mail::message')
# Your order is on its way! 📦

Hi {{ $order->customer_full_name ?? 'there' }},

Good news! Your order **#{{ $order->number }}** has been shipped and is on its way to you.

@if($trackingNumber)
---

## Tracking Information

**Tracking Number:** {{ $trackingNumber }}

@if($carrier)
**Carrier:** {{ $carrier }}
@endif

@component('mail::button', ['url' => config('app.url') . '/orders/' . $order->number . '/tracking'])
Track Your Order
@endcomponent

---
@endif

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

Thanks for your order!

{{ config('app.name') }}
@endcomponent