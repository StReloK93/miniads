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

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_name' => env('TELEGRAM_BOT_NAME'),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID') ?: env('TELEGRAM_CHANNEL'),
    ],

    // E'lon importi uchun AI. Model aniq nom bilan yoziladi ("-latest" alias emas),
    // aks holda Google uni jimgina qimmatroq modelga almashtirib qo'yadi.
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'minimal'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    // Bir kunda AI'ga sarflanadigan maksimal token (input + output). Oshsa, import keyingi kungacha to'xtaydi.
    'ai_daily_token_limit' => (int) env('AI_DAILY_TOKEN_LIMIT', 500000),

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
