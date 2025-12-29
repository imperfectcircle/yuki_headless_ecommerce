@component('mail::message')
# Thank you for your order 🎉

Hi {{ $order->customer_name ?? 'there' }},

Your order **#{{ $order->id }}** has been successfully confirmed.

---

## Order summary

@component('mail::table')
| Product | Quantity | Price |
|--------|----------|-------|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ number_format($item->total / 100, 2) }} {{ $order->currency }} |
@endforeach
@endcomponent

---

**Total:**  
**{{ number_format($order->grand_total / 100, 2) }} {{ $order->currency }}**

---

If you have any questions, just reply to this email.

Thanks,  
{{ config('app.name') }}
@endcomponent