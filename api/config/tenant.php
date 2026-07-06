<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default tenant slug
    |--------------------------------------------------------------------------
    |
    | The Flutter app sends no tenant identifier today, so unauthenticated
    | requests (catalog, GET /settings/app) resolve to this tenant. For a
    | single-store deployment this is the only tenant; multi-tenant clients
    | override it with the X-Tenant header or a subdomain.
    |
    */
    'default_slug' => env('DEFAULT_TENANT_SLUG', 'modist'),

    /*
    |--------------------------------------------------------------------------
    | Subdomain base host
    |--------------------------------------------------------------------------
    |
    | When set (e.g. "api.example.com"), a request to "acme.api.example.com"
    | resolves the tenant whose slug is "acme". Leave empty to disable
    | subdomain-based resolution.
    |
    */
    'base_host' => env('TENANT_BASE_HOST', ''),

];
