<?php

/*
|--------------------------------------------------------------------------
| Datos públicos de YURA (portal de marketing)
|--------------------------------------------------------------------------
|
| Todo viene del .env y vale null por defecto. Lo que no esté configurado
| simplemente no se muestra en el portal (no se inventan datos de contacto).
|
*/

return [

    // Nombre oficial de la unidad educativa o del equipo (pie y privacidad).
    'org_name' => env('YURA_ORG_NAME'),

    'contact' => [
        // Solo dígitos, con código de país. Ej.: 59171234567
        'whatsapp' => env('YURA_WHATSAPP'),
        'email' => env('YURA_EMAIL'),
        'address' => env('YURA_ADDRESS'),
        'hours' => env('YURA_HOURS'),
    ],

    // Enlace de descarga de la app (tienda, APK o página).
    'app_url' => env('YURA_APP_URL'),

    // Enlace del video corto del robot (YouTube, etc.).
    'video_url' => env('YURA_VIDEO_URL'),

    'social' => [
        'facebook' => env('YURA_FACEBOOK'),
        'tiktok' => env('YURA_TIKTOK'),
        'youtube' => env('YURA_YOUTUBE'),
    ],

    // Mínimo de registros para mostrar cifras reales en la portada.
    'stats_min' => (int) env('YURA_STATS_MIN', 10),

];
