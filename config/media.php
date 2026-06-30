<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Media sign (Glide / Curator) — rate limiting
    |--------------------------------------------------------------------------
    |
    | Limite richieste per minuto per IP sull'endpoint GET /api/v1/media/sign.
    |
    */
    'sign_max_attempts_per_minute' => (int) env('MEDIA_SIGN_MAX_ATTEMPTS_PER_MINUTE', 120),

];
