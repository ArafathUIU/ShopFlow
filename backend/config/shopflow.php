<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storefront pricing rules
    |--------------------------------------------------------------------------
    |
    | Flat-rate pricing used until real shipping/tax providers are wired in.
    | Shipping is free above the threshold.
    |
    */

    'tax_rate' => env('SHOPFLOW_TAX_RATE', 0.08),

    'flat_shipping' => env('SHOPFLOW_FLAT_SHIPPING', 5.00),

    'free_shipping_threshold' => env('SHOPFLOW_FREE_SHIPPING_THRESHOLD', 100.00),

];
