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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ewelink_cloud' => [
        'region' => env('EWELINK_CLOUD_REGION', 'eu'),
        'base_url' => env('EWELINK_CLOUD_BASE_URL', ''),
        'device_serials' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('EWELINK_CLOUD_DEVICE_SERIALS', '10024ff918'))
        ))),
        'app_id' => env('EWELINK_CLOUD_APP_ID', ''),
        'app_secret' => env('EWELINK_CLOUD_APP_SECRET', ''),
        'email' => env('EWELINK_CLOUD_EMAIL', ''),
        'password' => env('EWELINK_CLOUD_PASSWORD', ''),
        'oauth_code' => env('EWELINK_CLOUD_OAUTH_CODE', ''),
        'redirect_url' => env('EWELINK_CLOUD_REDIRECT_URL', ''),
        'access_token' => env('EWELINK_CLOUD_ACCESS_TOKEN', ''),
        'area_code' => env('EWELINK_CLOUD_AREA_CODE', '+48'),
    ],

];
