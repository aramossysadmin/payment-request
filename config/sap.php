<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAP Business One Service Layer
    |--------------------------------------------------------------------------
    |
    | URL del Service Layer de SAP B1 al que el portal se conectará para enviar
    | pagos. Es global (una sola URL para todo el portal). Cada sucursal guarda
    | sus credenciales individuales (database, branch_id, user, password) en la
    | tabla `branches`.
    |
    */

    'service_layer_url' => env('SAP_SERVICE_LAYER_URL'),

    'timeout' => (int) env('SAP_TIMEOUT', 30),

    'verify_ssl' => (bool) env('SAP_VERIFY_SSL', false),
];
