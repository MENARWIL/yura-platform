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

    /*
    | WhatsApp Cloud API (Meta): contraseñas temporales enviadas desde el número del colegio.
    | Sin token o sin ID del número queda apagado y se usa el botón manual (enlace wa.me).
    | template_kind: «authentication» (plantilla de código con botón «Copiar») o «utility»
    | (plantilla propia con 5 variables: nombre, contraseña, enlace, correo y vencimiento).
    */
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'template' => env('WHATSAPP_TEMPLATE', 'yura_clave_temporal'),
        'template_lang' => env('WHATSAPP_TEMPLATE_LANG', 'es'),
        'template_kind' => env('WHATSAPP_TEMPLATE_KIND', 'authentication'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
