@component('mail::message')
# Welcome to {{ config('app.name') }}! 🎉

Hi {{ $user->email }},

Your email has been verified successfully! We're thrilled to have you as part of our community.

You can now enjoy all the benefits of being a registered member:
- **Faster checkout** on future orders
- **Order tracking** and history
- **Saved addresses** for quick ordering
- **Exclusive offers** and early access to sales

@component('mail::button', ['url' => $shopUrl])
Start Shopping
@endcomponent

If you have any questions, feel free to reach out to our support team.

Thanks,  
{{ config('app.name') }} Team
@endcomponent