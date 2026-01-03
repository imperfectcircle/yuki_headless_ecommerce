<?php

namespace App\Domains\Customer\Actions;

use App\Domains\Customer\Events\GuestProfileLinked;
use App\Domains\Customer\Models\CustomerProfile;
use App\Domains\Customer\Models\StorefrontUser;
use App\Domains\Order\Models\Order;
use Illuminate\Support\Facades\DB;

class LinkGuestProfileToUser
{
    /**
     * Link guest profile and orders to registered user
     * 
     * @param StorefrontUser $user
     * @param string $guestEmail
     * @return CustomerProfile|null
     */
    public function execute(StorefrontUser $user, string $guestEmail): ?CustomerProfile
    {
        return DB::transaction(function () use ($user, $guestEmail) {
            // Find guest profile with matching email
            $guestProfile = CustomerProfile::query()
                ->where('email', $guestEmail)
                ->whereNull('storefront_user_id')
                ->first();

            if (!$guestProfile) {
                return null;
            }

            //Link profile to user
            $guestProfile->update([
                'storefront_user_id' => $user->id,
            ]);

            // Update all guest orders with this email
            $ordersLinked = Order::where('customer_email', $guestEmail)
                ->where('guest_checkout', true)
                ->whereNull('customer_profile_id')
                ->update([
                    'customer_profile_id' => $guestProfile->id,
                    'guest_checkout' => false
                ]);

            if ($ordersLinked > 0) {
                GuestProfileLinked::dispatch($user, $guestProfile, $ordersLinked);
            }

            return $guestProfile;
        });
    }
}
