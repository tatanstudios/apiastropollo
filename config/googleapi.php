<?php

return [

    'Google_API' => env('GOOGLE_API', ''),
    'Correo_API' => env('MAIL_FROM_ADDRESS', ''),

    /*
    | OneSignal: cada app tiene su App ID y su App API Key
    | (Panel OneSignal > Settings > Keys & IDs). Ver App\Services\OneSignalService.
    */
    'OneSignal' => [
        'cliente' => [
            'app_id' => env('ONE_SIGNAL_APP_CLIENTE', ''),
            'api_key' => env('ONE_SIGNAL_KEY_CLIENTE', ''),
        ],
        'restaurante' => [
            'app_id' => env('ONE_SIGNAL_APP_RESTAURANTE', ''),
            'api_key' => env('ONE_SIGNAL_KEY_RESTAURANTE', ''),
        ],
    ],

    // android_channel_id (categorias de notificacion creadas en cada app de OneSignal). Opcionales.
    'IdGrupoPasivoCliente' => env('ONE_SIGNAL_GRUPO_PASIVO_CLIENTE', ''),
    'IdGrupoPasivoRestaurante' => env('ONE_SIGNAL_GRUPO_PASIVO_RESTAURANTE', ''),

    // canal unico de la app del restaurante (sonido "notialarma"): todas sus notificaciones van aqui.
    // Si no se configura, se usa el pasivo.
    'CanalRestaurante' => env('ONE_SIGNAL_GRUPO_ALARMA_RESTAURANTE') ?: env('ONE_SIGNAL_GRUPO_PASIVO_RESTAURANTE', ''),
    'IdGrupoAlarmaRestaurante' => env('ONE_SIGNAL_GRUPO_ALARMA_RESTAURANTE', ''),

    // FCM HTTP v1: ruta al JSON de la cuenta de servicio de Firebase
    'FCM_Credenciales' => env('FCM_CREDENCIALES', storage_path('app/firebase/cuenta-servicio.json')),

];
