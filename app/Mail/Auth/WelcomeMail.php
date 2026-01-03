<?php

namespace App\Mail\Auth;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StorefrontUser $user
    ) {}

    public function build(): self
    {
        $shopUrl = config('app.frontend_url');

        return $this
            ->subject(__('mail.welcome_subject'))
            ->markdown('emails.auth.welcome', [
                'user' => $this->user,
                'shopUrl' => $shopUrl,
            ]);
    }
}
