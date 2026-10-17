<?php

/*
|--------------------------------------------------------------------------
| Mall floor layout
|--------------------------------------------------------------------------
| Every slot on every floor exists as a stall record, created automatically
| (`php artisan stalls:sync`, and whenever a manager opens the stall pages).
| Names are derived from the numbering, e.g. "Stall 12" or "Cart 3".
| Managers adjust prices, size, location and tenants.
*/

return [

    'floors' => [
        1 => [
            'label' => 'Floor 1 — Stalls',
            'prefix' => 'Stall',
            'count' => 24,
            'default_price' => (float) env('STALL_DEFAULT_PRICE_FLOOR_1', 5000),
        ],
        2 => [
            'label' => 'Floor 2 — Carts',
            'prefix' => 'Cart',
            'count' => 10,
            'default_price' => (float) env('STALL_DEFAULT_PRICE_FLOOR_2', 2500),
        ],
    ],

    /* How often (seconds) stall maps refresh availability in the browser. */
    'live_refresh_seconds' => (int) env('STALL_LIVE_REFRESH_SECONDS', 15),

];
