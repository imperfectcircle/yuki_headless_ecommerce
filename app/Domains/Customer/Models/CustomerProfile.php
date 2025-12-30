<?php

namespace App\Domains\Customer\Models;

use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'storefront_user_id',
        'email',
        'first_name',
        'last_name',
        'phone',
        'accepts_marketing',
    ];

    protected $casts = [
        'storefront_user_id' => 'integer',
        'accepts_marketing' => 'boolean',
    ];

    public function storefrontUser()
    {
        return $this->belongsTo(StorefrontUser::class, 'storefront_user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'customer_profile_id');
    }

    public function shippingAddresses()
    {
        return $this->addresses()->where('type', 'shipping');
    }

    public function billingAddresses()
    {
        return $this->addresses()->where('type', 'billing');
    }

    public function defaultShippingAddress()
    {
        return $this->shippingAddresses()->where('is_default', true)->first();
    }

    public function defaultBillingAddress()
    {
        return $this->billingAddresses()->where('is_default', true)->first();
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_profile_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isComplete(): bool
    {
        return !empty($this->first_name)
            && !empty($this->last_name)
            && $this->addresses()->exists();
    }

    public function isGuest(): bool
    {
        return is_null($this->storefront_user_id);
    }

    public function scopeGuest($query)
    {
        return $query->whereNull('storefront_user_id');
    }

    public function scopeRegistered($query)
    {
        return $query->whereNotNull('storefront_user_id');
    }
}
