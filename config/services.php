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

    /*
    | Google OAuth 2.0 (Sign up / Sign in with Google).
    |
    | The client secret is never stored in plain text. It is encrypted with
    | the application key (APP_KEY) and decrypted only at the moment the
    | OAuth handshake needs it. Generate the value with:
    |
    |     php artisan google:encrypt-secret
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret_encrypted' => env('GOOGLE_CLIENT_SECRET_ENCRYPTED'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
        'allowed_domains' => array_filter(array_map('trim', explode(',', env('GOOGLE_ALLOWED_DOMAINS', 'gmail.com')))),
    ],

    /*
    | PayMongo online payments (GCash, Maya, cards). Disabled until configured.
    | Both secrets are stored encrypted:  php artisan secret:encrypt paymongo-secret
    |                                     php artisan secret:encrypt paymongo-webhook
    | Register the webhook URL {APP_URL}/webhooks/paymongo for the event
    | "checkout_session.payment.paid".
    */
    'paymongo' => [
        'secret_key_encrypted' => env('PAYMONGO_SECRET_KEY_ENCRYPTED'),
        'webhook_secret_encrypted' => env('PAYMONGO_WEBHOOK_SECRET_ENCRYPTED'),
        'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),
        'payment_methods' => array_filter(array_map('trim', explode(',', env('PAYMONGO_PAYMENT_METHODS', 'gcash,paymaya,card')))),
        'webhook_tolerance' => (int) env('PAYMONGO_WEBHOOK_TOLERANCE', 300), // seconds
    ],

    /*
    | Predictive analytics (Python logistic-regression model in /ml).
    */
    'ml' => [
        'python' => env('ML_PYTHON_BINARY', 'python'),
        'timeout' => (int) env('ML_TIMEOUT', 15),
        'min_training_rows' => (int) env('ML_MIN_TRAINING_ROWS', 30),
    ],

];
