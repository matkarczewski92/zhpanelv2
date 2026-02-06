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

    'ewelink' => [
        'region' => env('EWELINK_CLOUD_REGION', 'eu'),
        'oauth_url' => env('EWELINK_CLOUD_OAUTH_URL', 'https://c2ccdn.coolkit.cc/oauth/index.html'),
        'dispatch_url' => env('EWELINK_CLOUD_BASE_URL'),
        'redirect_url' => env('EWELINK_CLOUD_REDIRECT_URL'),
        'app_id' => env('EWELINK_CLOUD_APP_ID'),
        'app_secret' => env('EWELINK_CLOUD_APP_SECRET'),
        'oauth_state' => env('EWELINK_CLOUD_OAUTH_STATE'),
        'api_domains' => [
            'cn' => env('EWELINK_CLOUD_API_DOMAIN_CN', 'cn-apia.coolkit.cn'),
            'as' => env('EWELINK_CLOUD_API_DOMAIN_AS', 'as-apia.coolkit.cc'),
            'us' => env('EWELINK_CLOUD_API_DOMAIN_US', 'us-apia.coolkit.cc'),
            'eu' => env('EWELINK_CLOUD_API_DOMAIN_EU', 'eu-apia.coolkit.cc'),
            'au' => env('EWELINK_CLOUD_API_DOMAIN_AU', 'au-apia.coolkit.info'),
            'test' => env('EWELINK_CLOUD_API_DOMAIN_TEST', 'test-apia.coolkit.cn'),
        ],
    ],

];
