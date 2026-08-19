<?php

/*
| PayForGoals display constants. The brand name is driven from APP_NAME so the
| whole demo can be renamed in one place; pricing mirrors the route middleware
| in routes/api.php (keep them in sync if you change the gates).
*/

return [
    'brand' => env('APP_NAME', 'PayForGoals'),
    'tagline' => 'Relive football\'s greatest scorelines on demand.',

    // One route price covers both rails. Tempo pays the same number in pathUSD;
    // USD pricing keeps the shared route above Stripe's card minimum.
    'pricing' => [
        'match' => '1.00',
        'classics' => '3.00',
        'currency' => 'USD',
        'grants' => 3,
    ],

    // Tempo testnet settlement facts, surfaced for the education sections.
    'tempo' => [
        'network' => 'testnet',
        'chain_id' => 42431,
        'recipient' => env('TEMPO_RECIPIENT', '0x0dcd39A3F85aa288C1B2825bc41EB7e9BB2ABF70'),
        'token' => env('TEMPO_TOKEN', '0x20c0000000000000000000000000000000000000'),
        'rpc_url' => env('TEMPO_RPC_URL', 'https://rpc.moderato.tempo.xyz'),
        'decimals' => (int) env('TEMPO_DECIMALS', 6),
    ],

    'links' => [
        'square1' => 'https://www.square1.io',
        'package' => 'https://github.com/square1-io/laravel-mpp',
        'source' => 'https://github.com/square1-io/www.payforgoals.com',
        'mpp' => 'https://mpp.dev',
        'payment_auth' => 'https://paymentauth.org/draft-httpauth-payment-00',
        'tempo_explorer' => 'https://explore.testnet.tempo.xyz',
        'stripe_spt' => 'https://docs.stripe.com/agentic-commerce/concepts/shared-payment-tokens',
    ],
];
