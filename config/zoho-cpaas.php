<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Zoho Region & Data Center
    |--------------------------------------------------------------------------
    |
    | Supported regions: 'us', 'eu', 'in', 'au', 'ca', 'sa', 'cn', 'custom'.
    | The region automatically configures the appropriate ZeptoMail API endpoint
    | and Zoho Accounts OAuth server.
    |
    */
    'region' => env('ZOHO_CPAAS_REGION', env('ZEPTOMAIL_REGION', 'us')),

    /*
    |--------------------------------------------------------------------------
    | Custom Endpoints (Optional)
    |--------------------------------------------------------------------------
    |
    | When region is set to 'custom', you can specify the exact base URL
    | for ZeptoMail API and Zoho Accounts authorization server.
    |
    */
    'api_url' => env('ZOHO_CPAAS_API_URL', env('ZEPTOMAIL_API_URL', null)),
    'accounts_url' => env('ZOHO_CPAAS_ACCOUNTS_URL', env('ZEPTOMAIL_ACCOUNTS_URL', null)),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The ZeptoMail API version to target. Default is 'v1.1'.
    |
    */
    'version' => env('ZOHO_CPAAS_API_VERSION', 'v1.1'),

    /*
    |--------------------------------------------------------------------------
    | Send Mail Token (For Email Management APIs)
    |--------------------------------------------------------------------------
    |
    | The Send Mail API key is obtained from your Mail Agent under:
    | Agents -> SMTP/API -> API tab -> Copy token.
    | Used for sending single/batch emails, templates, and file cache upload.
    |
    */
    'send_mail_token' => env('ZOHO_CPAAS_SEND_MAIL_TOKEN', env('ZEPTOMAIL_SEND_MAIL_TOKEN', '')),

    /*
    |--------------------------------------------------------------------------
    | Default Bounce Address (Optional)
    |--------------------------------------------------------------------------
    |
    | Configured bounce subdomain/address where bounce notifications are directed.
    | E.g.: 'bounce@bounce.yourdomain.com'
    |
    */
    'bounce_address' => env('ZOHO_CPAAS_BOUNCE_ADDRESS', null),

    /*
    |--------------------------------------------------------------------------
    | Default Mail Agent Alias (Optional)
    |--------------------------------------------------------------------------
    |
    | Used when interacting with Mail Agent templates or agent-specific APIs.
    |
    */
    'mailagent_alias' => env('ZOHO_CPAAS_MAILAGENT_ALIAS', env('ZEPTOMAIL_MAILAGENT_ALIAS', null)),

    /*
    |--------------------------------------------------------------------------
    | OAuth 2.0 Credentials (For Management & Admin APIs)
    |--------------------------------------------------------------------------
    |
    | Required for managing Templates, Domains, Agents, Suppression lists,
    | and Email Logs. Generate client credentials in Zoho API Console (Self Client).
    |
    */
    'oauth' => [
        'client_id' => env('ZOHO_CPAAS_CLIENT_ID', env('ZEPTOMAIL_CLIENT_ID', '')),
        'client_secret' => env('ZOHO_CPAAS_CLIENT_SECRET', env('ZEPTOMAIL_CLIENT_SECRET', '')),
        'refresh_token' => env('ZOHO_CPAAS_REFRESH_TOKEN', env('ZEPTOMAIL_REFRESH_TOKEN', '')),
        'access_token' => env('ZOHO_CPAAS_ACCESS_TOKEN', env('ZEPTOMAIL_ACCESS_TOKEN', '')),
        'redirect_uri' => env('ZOHO_CPAAS_REDIRECT_URI', null),

        /*
        | Storage driver for caching generated OAuth access tokens.
        | Options: 'cache' (Laravel Cache), 'file' (Local disk), 'array'
        */
        'token_store' => env('ZOHO_CPAAS_TOKEN_STORE', 'cache'),
        'cache_key' => 'zoho_cpaas_access_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Sender
    |--------------------------------------------------------------------------
    |
    | The default "from" email and name used if not specified in the message.
    |
    */
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@yourdomain.com'),
        'name' => env('MAIL_FROM_NAME', 'Example App'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Tracking Settings
    |--------------------------------------------------------------------------
    |
    | Control whether email open and click tracking should be enabled by default.
    |
    */
    'tracking' => [
        'track_clicks' => env('ZOHO_CPAAS_TRACK_CLICKS', true),
        'track_opens' => env('ZOHO_CPAAS_TRACK_OPENS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure timeout, retry count, and retry backoff for requests.
    |
    */
    'http' => [
        'timeout' => 30.0,
        'connect_timeout' => 10.0,
        'retries' => 2,
        'retry_delay_ms' => 500,
    ],
];
