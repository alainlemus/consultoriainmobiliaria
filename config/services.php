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

    'firebase' => [
        'credentials'  => env('FIREBASE_CREDENTIALS'),
        'project_id'   => env('FIREBASE_PROJECT_ID'),
    ],

    'openwa' => [
        'url'            => env('OPENWA_URL', 'http://localhost:2785'),
        'api_key'        => env('OPENWA_API_KEY', 'dev-admin-key'),
        'session'        => env('OPENWA_SESSION', ''),
        'webhook_secret' => env('OPENWA_WEBHOOK_SECRET', ''),
    ],

    // API del Banco de Indicadores del INEGI — para actualizar la UMA automáticamente.
    // Token gratuito en: https://www.inegi.org.mx/app/api/indicadores/
    'inegi' => [
        'token' => env('INEGI_TOKEN', ''),
    ],

];
