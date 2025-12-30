<?php

namespace App\Domains\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class StorefrontUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'email_verified',
        'email_verified_at',
        'verification_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected $casts = [
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(CustomerProfile::class, 'storefront_user_id');
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified === true;
    }

    public function markEmailAsVerified(): void
    {
        $this->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'verification_token' => null,
        ]);
    }

    public function generateVerificationToken(): string
    {
        $token = Str::random(64);
        $this->update(['verification_token' => $token]);
        return $token;
    }
}
