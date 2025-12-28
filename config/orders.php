<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Reservation Timeout
    |--------------------------------------------------------------------------
    |
    | The number of minutes that inventory should be reserved for an order
    | before it expires. After this time, if payment hasn't been completed,
    | the order will be cancelled and inventory released.
    |
    */
    'reservation_timeout' => env('ORDER_RESERVATION_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Order Number Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix used for generating order numbers.
    |
    */
    'number_prefix' => env('ORDER_NUMBER_PREFIX', 'ORD'),

    /*
    |--------------------------------------------------------------------------
    | Order Number Format
    |--------------------------------------------------------------------------
    |
    | The format for order numbers. Available placeholders:
    | {prefix} - The prefix defined above
    | {year} - Current year (4 digits)
    | {month} - Current month (2 digits)
    | {day} - Current day (2 digits)
    | {random} - Random 6-digit number
    |
    */
    'number_format' => '{prefix}-{year}-{random}',

    /*
    |--------------------------------------------------------------------------
    | Order Statuses
    |--------------------------------------------------------------------------
    |
    | Available order statuses and their descriptions.
    |
    */
    'statuses' => [
        'draft' => 'Order created but inventory not reserved',
        'reserved' => 'Inventory reserved, awaiting payment',
        'paid' => 'Payment completed, inventory confirmed',
        'cancelled' => 'Order cancelled, inventory released',
    ],
];
