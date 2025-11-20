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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ai' => [
        // Đổi AI_SERVICE_URL -> AI_BASE_URL cho khớp .env
        'base_url' => env('AI_BASE_URL', 'http://localhost:8000'),
        'use_gemini_for_display' => env('USE_GEMINI_FOR_DISPLAY', false),
    ],

    'gemini' => [
        // Đổi GOOGLE_API_KEY -> GEMINI_API_KEY cho khớp .env
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base' => rtrim(env('GEMINI_BASE', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
    ],

];
