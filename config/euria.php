<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Infomaniak API Token
    |--------------------------------------------------------------------------
    | Default OAuth2 token. Can be overridden via ->withToken() call.
    */

    'api_token' => env('INFOMANIAK_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Infomaniak AI Services API Base URL
    |--------------------------------------------------------------------------
    */

    'base_url' => env('INFOMANIAK_AI_BASE_URL', 'https://api.infomaniak.com/1/ai'),

    /*
    |--------------------------------------------------------------------------
    | Default models per capability
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'text'       => env('EURIA_DEFAULT_TEXT_MODEL', 'mixtral'),
        'embedding'  => env('EURIA_DEFAULT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'image'      => env('EURIA_DEFAULT_IMAGE_MODEL', 'sdxl'),
        'audio'      => env('EURIA_DEFAULT_AUDIO_MODEL', 'whisper-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout (seconds)
    |--------------------------------------------------------------------------
    */

    'timeout' => env('EURIA_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Image generation options
    |--------------------------------------------------------------------------
    */

    'image' => [
        'default_format' => env('EURIA_IMAGE_FORMAT', 'square'),
        'default_count'  => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation persistence
    |--------------------------------------------------------------------------
    */

    'conversations' => [
        'table'   => 'euria_conversations',
        'messages_table' => 'euria_conversation_messages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event logging
    |--------------------------------------------------------------------------
    */

    'events' => [
        'enabled' => env('EURIA_EVENTS_ENABLED', true),
    ],
];
