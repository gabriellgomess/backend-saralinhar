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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'models'  => [
            'transcription'   => env('OPENAI_MODEL_TRANSCRIPTION',   'gpt-4o-transcribe'),
            'contact_extract' => env('OPENAI_MODEL_CONTACT_EXTRACT', 'gpt-5-nano'),
            'resume_analysis' => env('OPENAI_MODEL_RESUME_ANALYSIS', 'gpt-5.4-nano'),
            'report'          => env('OPENAI_MODEL_REPORT',          'gpt-5.4-nano'),
            'player'          => env('OPENAI_MODEL_PLAYER',          'gpt-5.4-nano'),
            'validation'      => env('OPENAI_MODEL_VALIDATION',      'gpt-5-nano'),
            'disc'            => env('OPENAI_MODEL_DISC',            'gpt-5.4-nano'),
            'ocr'             => env('OPENAI_MODEL_OCR',             'gpt-5.4-nano'),
        ],

        // Preços em USD por 1.000.000 tokens (formato padrão da OpenAI).
        // Ajuste conforme a tabela de preços vigente — consulte https://openai.com/api/pricing
        // Modelos não listados terão estimated_cost_usd = null no log.
        'pricing' => [
            'gpt-4o-transcribe' => [
                'input'  => 2.50,
                'output' => 10.00,
                'cached_input' => 1.25,
            ],
            'gpt-4o-mini-transcribe' => [
                'input'        => 1.25,
                'output'       => 5.00,
                'cached_input' => 0.625,
            ],
            'gpt-5-nano' => [
                'input'  => 0.05,
                'output' => 0.40,
                'cached_input' => 0.005,
            ],
            'gpt-5.4-nano' => [
                'input'  => 0.20,
                'output' => 1.25,
                'cached_input' => 0.02,
            ],
            'gpt-4o-mini' => [
                'input'  => 0.15,
                'output' => 0.60,
                'cached_input' => 0.075,
            ],
            'gpt-4o' => [
                'input'  => 2.50,
                'output' => 10.00,
                'cached_input' => 1.25,
            ],
        ],
    ],

    'evolution' => [
        'api_url' => env('EVOLUTION_API_URL', 'http://localhost:8080'),
        'api_key' => env('EVOLUTION_API_KEY'),
        'instance_name' => env('EVOLUTION_INSTANCE_NAME', 'default'),
        'phone_number' => env('EVOLUTION_PHONE_NUMBER', '5551997073430'),
    ],

    'frontend' => [
        'url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    ],

];
