<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ignored addresses
    |--------------------------------------------------------------------------
    |
    | Hits from these addresses are never recorded. Production runs on a home
    | network, so without this every time the site is opened from the same house
    | it shows up as a visitor — and on a portfolio that sees a handful of real
    | visits a week, that is most of the data.
    |
    | Comma-separated, and each entry may be a single address or a CIDR range
    | ("203.0.113.4, 2001:db8::/32"). `trustProxies` is set to "*", so what is
    | matched is the real client address, not Cloudflare's.
    |
    */

    'ignored_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ANALYTICS_IGNORED_IPS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Ignored paths
    |--------------------------------------------------------------------------
    |
    | Belt and braces. The beacon script only ships with the public page, so the
    | admin should never report a hit in the first place.
    |
    */

    'ignored_paths' => [
        'admin',
        'admin/*',
        'livewire/*',
    ],

];
