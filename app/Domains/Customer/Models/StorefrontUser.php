<?php

namespace App\Domains\Customer\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property bool $email_verified
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $verification_token
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Domains\Customer\Models\CustomerProfile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereEmailVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorefrontUser whereVerificationToken($value)
 * @mixin \Eloquent
 */
class StorefrontUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
