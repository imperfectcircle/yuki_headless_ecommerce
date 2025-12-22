<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Reservation Timeout
    |--------------------------------------------------------------------------
    |
    | This value determines the number of minutes an order's inventory will be
    | reserved for when the order is placed in the "reserved" status. After
    | this period, the reservation will expire and inventory released.
    |
    */

    'reservation_timeout' => env('ORDER_RESERVATION_TIMEOUT', 15),
];
