@component('mail::message')
# Verify your email address

Hi {{ $user->email }},

Thanks for signing up! Please verify your email address to complete your registration.

@component('mail::button', ['url' => $verificationUrl])
Verify Email Address
@endcomponent

This link will expire in 24 hours.

If you didn't create an account, you can safely ignore this email.

Thanks,  
{{ config('app.name') }}
@endcomponent