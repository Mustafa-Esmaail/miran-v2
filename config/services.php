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

    'clickpay' => [
        'base_url' => env('CLICKPAY_BASE_URL'),
        'server_key' => env('CLICKPAY_SERVER_KEY'),
        'profile_id' => env('CLICKPAY_PROFILE_ID'),
    ],

    'tabby' => [
        'secret_key' => env('TABBY_SECRET_KEY'),
    ],

    'tamara' => [
        'secret_key' => env('TAMARA_SECRET_KEY'),
        'init_api_key' => env('TAMARA_INIT_API_KEY'),
        'payment_type' => env('TAMARA_PAYMENT_TYPE', 'PAY_BY_INSTALMENTS'),
        'instalments' => (int) env('TAMARA_INSTALMENTS', 4),
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
    ],

    'zoom' => [
        'account_id' => env('ZOOM_ACCOUNT_ID'),
        'client_id' => env('ZOOM_CLIENT_ID'),
        'client_secret' => env('ZOOM_CLIENT_SECRET'),
        'webhook_secret' => env('ZOOM_WEBHOOK_SECRET'),
    ],

    'fcm' => [
        'credentials' => env('FIREBASE_CREDENTIALS') ?? storage_path('app/firebase/firebase_cred.json'),
        'project_id' => env('FIREBASE_PROJECT_ID') ?? 'naqi-tech',
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'nelc' => [
        'enabled' => (bool) env('NELC_SYNC_ENABLED', false),
        'lrs_endpoint' => env('NELC_LRS_ENDPOINT', 'https://lrs.nelc.gov.sa/lrs-license-stg/xapi/statements'),
        'lrs_username' => env('NELC_LRS_USERNAME', 'develop_themiran_net'),
        'lrs_password' => env('NELC_LRS_PASSWORD', '6o0V#6%2fF6l'),
        'platform_ar' => env('NELC_PLATFORM_AR', 'https://develop.themiran.net'),
        'platform_en' => env('NELC_PLATFORM_EN', 'https://develop.themiran.net'),
    ],

    'telegram_errors' => [
        'enabled' => (bool) env('TELEGRAM_ERROR_LOG_ENABLED', true),
        'bot_token' => env('TELEGRAM_ERROR_LOG_BOT_TOKEN', '8814211244:AAFGjBq7cSEXXj5Fr3iA7JGbwchwgw5sQZE'),
        'chat_id' => env('TELEGRAM_ERROR_LOG_CHAT_ID', '-1004440452625'),
        'topic_id' => env('TELEGRAM_ERROR_LOG_TOPIC_ID'),
        'level' => env('TELEGRAM_ERROR_LOG_LEVEL', 'error'),
        'dedup_seconds' => (int) env('TELEGRAM_ERROR_LOG_DEDUP_SECONDS', 300),
        'dedup_store' => env('TELEGRAM_ERROR_LOG_DEDUP_STORE', 'file'),
        'timeout' => (int) env('TELEGRAM_ERROR_LOG_TIMEOUT', 5),
    ],

];
