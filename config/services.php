<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'erpnext' => [
        'url'           => env('ERPNEXT_URL'),
        'api_key'       => env('ERPNEXT_API_KEY'),
        'api_secret'    => env('ERPNEXT_API_SECRET'),
        'lead_source'   => env('ERPNEXT_LEAD_SOURCE', 'WhatsApp Bot'),
        'lead_owner'    => env('ERPNEXT_LEAD_OWNER'),
        'lead_doctype'  => env('ERPNEXT_LEAD_DOCTYPE', 'CRM Lead'),
        'timeout'       => (int) env('ERPNEXT_TIMEOUT', 5),
    ],

];
