<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Infomaniak API Token
    |--------------------------------------------------------------------------
    | Token OAuth2 par défaut. Peut être surchargé par appel via ->withToken().
    */

    'api_token' => env('INFOMANIAK_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Base URL de l'API Infomaniak AI Services
    |--------------------------------------------------------------------------
    */

    'base_url' => env('INFOMANIAK_AI_BASE_URL', 'https://api.infomaniak.com/1/ai'),

    /*
    |--------------------------------------------------------------------------
    | Modèles par défaut par capacité
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'text' => env('EURIA_DEFAULT_TEXT_MODEL', 'mixtral'),
        'embedding' => env('EURIA_DEFAULT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'image' => env('EURIA_DEFAULT_IMAGE_MODEL', 'sdxl'),
        'audio' => env('EURIA_DEFAULT_AUDIO_MODEL', 'whisper-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout HTTP (secondes)
    |--------------------------------------------------------------------------
    */

    'timeout' => env('EURIA_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Options de génération d'images
    |--------------------------------------------------------------------------
    */

    'image' => [
        'default_format' => env('EURIA_IMAGE_FORMAT', 'square'),
        'default_count' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistance des conversations
    |--------------------------------------------------------------------------
    */

    'conversations' => [
        'table' => 'euria_conversations',
        'messages_table' => 'euria_conversation_messages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging des events
    |--------------------------------------------------------------------------
    */

    'events' => [
        'enabled' => env('EURIA_EVENTS_ENABLED', true),
    ],
];
