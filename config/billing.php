<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Late fees
    |--------------------------------------------------------------------------
    | Applied once, when a rent or electric bill becomes overdue
    | (fee = amount × percent / 100 + fixed).
    */
    'late_fee' => [
        'percent' => (float) env('LATE_FEE_PERCENT', 5),
        'fixed' => (float) env('LATE_FEE_FIXED', 0),
        'grace_days' => (int) env('LATE_FEE_GRACE_DAYS', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual GCash payments (verified by a manager / admin)
    |--------------------------------------------------------------------------
    */
    'gcash' => [
        'account_name' => env('GCASH_ACCOUNT_NAME', 'Technomart'),
        'account_number' => env('GCASH_ACCOUNT_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Official receipt numbering
    |--------------------------------------------------------------------------
    | Receipts are numbered sequentially per year: OR-2026-000001.
    */
    'receipt_prefix' => env('RECEIPT_PREFIX', 'OR'),

    /*
    |--------------------------------------------------------------------------
    | Supply orders
    |--------------------------------------------------------------------------
    | Unpaid orders reserve stock; they are cancelled automatically after this many days.
    */
    'order_auto_cancel_days' => (int) env('ORDER_AUTO_CANCEL_DAYS', 3),

];
