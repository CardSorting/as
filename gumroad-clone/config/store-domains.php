<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Domain Configuration
    |--------------------------------------------------------------------------
    |
    | The base domain used for all stores. Stores will be created as subdomains
    | of this domain unless they have a custom domain configured.
    |
    */
    'base_domain' => env('STORE_BASE_DOMAIN', 'gumroad-clone.test'),

    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the wildcard SSL certificate used across all stores.
    | This avoids the need to generate individual certificates for each store.
    |
    */
    'ssl' => [
        'wildcard_cert' => env('SSL_WILDCARD_CERT', '/etc/ssl/certs/wildcard.gumroad-clone.test.pem'),
        'wildcard_key' => env('SSL_WILDCARD_KEY', '/etc/ssl/private/wildcard.gumroad-clone.test.key'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Domain Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for handling custom domains. This includes verification
    | requirements and DNS settings.
    |
    */
    'custom_domains' => [
        'enabled' => true,
        'verification_required' => true,
        'allowed_tiers' => ['pro', 'enterprise'],
        'max_domains' => [
            'basic' => 0,
            'pro' => 1,
            'enterprise' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | DNS Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for DNS management, including provider configuration and
    | verification methods.
    |
    */
    'dns' => [
        'provider' => env('DNS_PROVIDER', 'cloudflare'),
        'zone_id' => env('DNS_ZONE_ID'),
        'verification_type' => 'CNAME', // CNAME or TXT
        'propagation_timeout' => 300,    // 5 minutes
        'check_interval' => 10,          // 10 seconds
    ],
];
