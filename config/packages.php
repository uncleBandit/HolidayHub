<?php

/*
|--------------------------------------------------------------------------
| Agent packages
|--------------------------------------------------------------------------
|
| A package is priced in one currency, chosen by its agent, but the stays and
| experiences inside it are priced live in their own listing currencies. When
| the two differ, PackagePricing converts the listing price with the table
| below and flags the quote line as converted.
|
| `rates` is how many units of each currency buy ONE US dollar. These are
| APPROXIMATE reference rates for showing a fair package price; they are not
| live FX and nothing is settled with them. Each provider is still paid, and
| each component booking is still recorded, in the listing's own currency.
| Update them by hand (or override per environment) when they drift.
|
| A currency missing from the table cannot be converted: a quote line in it
| is shown as unavailable, so the agent sees it and picks another currency.
*/

return [

    'rates' => [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'CHF' => 0.88,
        'KES' => 129.0,
        'UGX' => 3700.0,
        'TZS' => 2650.0,
        'RWF' => 1380.0,
        'ETB' => 120.0,
        'ZAR' => 18.2,
        'NGN' => 1550.0,
        'GHS' => 15.5,
        'MAD' => 9.9,
        'EGP' => 48.5,
        'AED' => 3.67,
        'INR' => 84.0,
        'JPY' => 150.0,
        'CNY' => 7.2,
        'AUD' => 1.52,
        'CAD' => 1.37,
        'NZD' => 1.66,
        'MUR' => 46.0,
        'SCR' => 13.8,
        'MZN' => 63.9,
        'BWP' => 13.6,
        'NAD' => 18.2,
        'ZMW' => 26.5,
    ],

    // The guest page's first suggested departure is this many days ahead.
    'default_lead_days' => 30,

    // How many guests the builder's sample quote is for, before the agent sets min/max.
    'sample_guests' => 2,

];
