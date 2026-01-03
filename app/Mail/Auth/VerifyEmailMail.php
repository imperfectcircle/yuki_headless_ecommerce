<?php

namespace App\Mail\Auth;

use App\Domains\Customer\Models\StorefrontUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StorefrontUser $user,
    ) {}

    public function build(): self
    {
        $verificationUrl = config('app.frontend_url')
            . '/verify-account?token='
            . $this->user->verification_token;

        return $this
            ->subject(__('mail.verify_email_subject'))
            ->markdown('emails.auth.verify-email', [
                'user' => $this->user,
                'verificationUrl' => $verificationUrl,
            ]);
    }
}
