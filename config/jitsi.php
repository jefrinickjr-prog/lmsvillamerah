<?php

return [
    'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),
    'room_prefix' => env('JITSI_ROOM_PREFIX', 'VillaMerahBeta'),
    'app_id' => env('JITSI_APP_ID'),
    'key_id' => env('JITSI_KEY_ID'),
    'private_key_path' => env('JITSI_PRIVATE_KEY_PATH'),
    'private_key_base64' => env('JITSI_PRIVATE_KEY_BASE64'),
    'max_token_hours' => (int) env('JITSI_MAX_TOKEN_HOURS', 6),
];
