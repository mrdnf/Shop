<?php
// The web installer (/install) writes config/config.php for you.
// This file documents every option.
return [
    'installed' => true,
    'app_name' => 'Matjar',
    'app_key' => 'base64-32-random-bytes',     // encrypts DB passwords & integration secrets: never change after install
    'mode' => 'single',                        // 'single' = one shop | 'multi' = SaaS with many shops
    'base_domain' => '',                       // multi: 'myplatform.com' => shops at {slug}.myplatform.com
    'timezone' => 'UTC',
    'debug' => false,
    'allow_private_hosts' => false,            // multi: let shops connect integrations to private IPs
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'dnf-shops',                 // one user creates the master DB and every shop DB
        'pass' => '',
        'platform_db' => 'master',
        'tenant_prefix' => '',                  // '' = each shop DB is named exactly like the shop
        'per_tenant_user' => false,            // create a dedicated MySQL user per shop
        'tenant_user_host' => '%',
    ],
];
