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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    'rajaongkir' => [
        'mode' => env('RAJAONGKIR_MODE', 'starter'),
        'key' => env('RAJAONGKIR_API_KEY'),
        'key_header' => env('RAJAONGKIR_KEY_HEADER', 'key'),
        'base_url' => env('RAJAONGKIR_BASE_URL', 'https://api.rajaongkir.com/starter'),
        'origin_city_id' => env('RAJAONGKIR_ORIGIN_CITY_ID'),
        'origin_district_id' => env('RAJAONGKIR_ORIGIN_DISTRICT_ID'),
        'default_weight' => env('RAJAONGKIR_DEFAULT_WEIGHT', 1000),
    ],

    'local_delivery' => [
        'fee' => env('LOCAL_DELIVERY_FEE', 10000),
    ],

    'finance' => [
        'default_cogs_percentage' => env('DEFAULT_COGS_PERCENTAGE', 40),
    ],

];
