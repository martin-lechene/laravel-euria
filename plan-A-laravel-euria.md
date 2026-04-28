# Plan A — Package Standalone `martin-lechene/laravel-euria`

> Package Laravel 10–13 / PHP 8.1–8.4 pour l'API Infomaniak AI Services (Euria)  
> Inspiré de `laravel/ai` — style, conventions, architecture identiques  
> Distribution : Packagist public · Licence MIT

---

## Table des matières

1. [Contexte & objectifs](#1-contexte--objectifs)
2. [Prérequis & contraintes techniques](#2-prérequis--contraintes-techniques)
3. [Architecture générale](#3-architecture-générale)
4. [Structure de fichiers complète](#4-structure-de-fichiers-complète)
5. [composer.json — manifeste Composer](#5-composerjson--manifeste-composer)
6. [Service Provider](#6-service-provider)
7. [Configuration `config/euria.php`](#7-configuration-configeuriaphp)
8. [Authentification — token défaut + override par appel](#8-authentification--token-défaut--override-par-appel)
9. [Client HTTP Infomaniak](#9-client-http-infomaniak)
10. [Capacité 1 — LLM Text (Chat / Completion)](#10-capacité-1--llm-text-chat--completion)
11. [Capacité 2 — Streaming SSE](#11-capacité-2--streaming-sse)
12. [Capacité 3 — Function Calling / Tool Use](#12-capacité-3--function-calling--tool-use)
13. [Capacité 4 — Embeddings](#13-capacité-4--embeddings)
14. [Capacité 5 — Image Generation (SDXL / Flux)](#14-capacité-5--image-generation-sdxl--flux)
15. [Capacité 6 — Audio Transcription (Whisper STT)](#15-capacité-6--audio-transcription-whisper-stt)
16. [Facade `Euria::` et helper `euria()`](#16-facade-euria-et-helper-euria)
17. [Système d'Agents (style laravel/ai)](#17-système-dagents-style-laravelai)
18. [Events Laravel](#18-events-laravel)
19. [EuriaFake — Testing Layer complet](#19-euriafake--testing-layer-complet)
20. [Commandes Artisan](#20-commandes-artisan)
21. [Suite de tests Pest PHP](#21-suite-de-tests-pest-php)
22. [Qualité de code — PHPStan level 9 + Laravel Pint](#22-qualité-de-code--phpstan-level-9--laravel-pint)
23. [CI/CD — GitHub Actions](#23-cicd--github-actions)
24. [Documentation](#24-documentation)
25. [Publication Packagist](#25-publication-packagist)
26. [Roadmap de développement (phases)](#26-roadmap-de-développement-phases)

---

## 1. Contexte & objectifs

### Pourquoi ce package ?

`laravel/ai` supporte OpenAI, Anthropic, Gemini, Mistral, etc. — mais **aucun provider Infomaniak/Euria** n'existe aujourd'hui. Or l'API Infomaniak AI Services est **compatible OpenAI** (même format de requêtes/réponses JSON), ce qui rend le bridging relativement direct, tout en nécessitant une gestion propre de l'authentification OAuth2, des endpoints spécifiques, et des modèles propres à la plateforme (Mixtral, Llama, DeepSeek, SDXL, Flux, Whisper).

### Objectifs du package

- Offrir une interface **Laravel-native** (Facade, Agents, Events, Fake) pour toutes les capacités Euria
- Respecter les conventions de `laravel/ai` pour que les devs puissant passer de l'un à l'autre sans friction
- Être **publié sur Packagist** et maintenu publiquement
- Supporter PHP 8.1–8.4 et Laravel 10–13 (matrice large)

---

## 2. Prérequis & contraintes techniques

| Paramètre | Valeur |
|---|---|
| PHP minimum | 8.1 |
| PHP maximum testé | 8.4 |
| Laravel minimum | 10.x |
| Laravel maximum | 13.x |
| Dépendances Composer | `illuminate/support`, `illuminate/http`, `guzzlehttp/guzzle` |
| API Infomaniak base URL | `https://api.infomaniak.com` |
| Auth | Bearer token (OAuth2 API token Infomaniak) |
| Rate limit Infomaniak | 60 req/min (à gérer dans les tests et le client) |
| Facturation | Par token LLM (in + out) |
| Format API | Compatible OpenAI (JSON) |
| Modèles LLM disponibles | Mistral, Mixtral, Llama 3, DeepSeek, etc. |
| Modèles image | SDXL, Flux |
| Modèle STT | Whisper |
| Modèle embeddings | selon catalogue Infomaniak |

### Contraintes connues

- L'endpoint exact de l'API Infomaniak AI Services nécessitera une vérification contre la doc officielle (`developer.infomaniak.com/docs/api`) — les endpoints précis pour chaque capacité doivent être mappés dans la config
- La compatibilité OpenAI signifie que le format des messages (`role`, `content`) est identique, mais l'auth et la base URL diffèrent
- Pas de support Function Calling garanti sur tous les modèles — le package doit gérer les erreurs proprement

---

## 3. Architecture générale

```
Facade Euria:: / helper euria()
         │
         ▼
   EuriaManager  (Driver Manager pattern Laravel)
         │
    ┌────┴────────────────┐
    │                     │
EuriaClient          EuriaAgentRunner
(HTTP + Auth)        (Agent lifecycle)
    │                     │
    ├── TextDriver         ├── Agent contracts
    ├── EmbeddingDriver    ├── Tool resolution
    ├── ImageDriver        ├── Conversation memory
    ├── AudioDriver        └── Structured output
    └── StreamDriver
         │
    InfromaniakHttpClient
    (Guzzle + Bearer token)
         │
    Events dispatcher
    (RequestSent, ResponseReceived,
     TokensUsed, StreamChunkReceived)
         │
    EuriaFake (testing swap)
```

Le pattern est un **Driver Manager** standard Laravel (`Illuminate\Support\Manager`) — exactement comme `laravel/ai`. Chaque capacité a son propre driver encapsulé, mais partage le même client HTTP authentifié.

---

## 4. Structure de fichiers complète

```
martin-lechene/laravel-euria/
│
├── art/
│   └── logo.svg                          # Logo du package (style laravel/ai)
│
├── config/
│   └── euria.php                         # Config publiable
│
├── database/
│   └── migrations/
│       └── create_euria_conversations_table.php
│       └── create_euria_conversation_messages_table.php
│
├── resources/
│   └── stubs/                            # Stubs pour les commandes Artisan
│       └── agent.stub
│       └── agent.structured.stub
│       └── tool.stub
│
├── src/
│   ├── EuriaServiceProvider.php          # Service Provider principal
│   ├── EuriaManager.php                  # Driver Manager
│   ├── EuriaFacade.php                   # Facade Euria::
│   │
│   ├── Client/
│   │   ├── InfomaniakHttpClient.php      # Client HTTP Guzzle + auth
│   │   └── PendingRequest.php            # Fluent builder pour les requêtes
│   │
│   ├── Drivers/
│   │   ├── TextDriver.php                # LLM Chat / Completion
│   │   ├── StreamDriver.php              # Streaming SSE
│   │   ├── EmbeddingDriver.php           # Embeddings
│   │   ├── ImageDriver.php               # Image generation SDXL / Flux
│   │   └── AudioDriver.php               # Whisper STT
│   │
│   ├── Contracts/
│   │   ├── Agent.php                     # Interface Agent
│   │   ├── Conversational.php            # Interface Conversational
│   │   ├── HasTools.php                  # Interface HasTools
│   │   ├── HasStructuredOutput.php       # Interface HasStructuredOutput
│   │   └── EuriaDriver.php               # Interface commune des drivers
│   │
│   ├── Concerns/
│   │   ├── Promptable.php                # Trait Promptable (prompt/stream/queue)
│   │   └── RemembersConversations.php    # Trait persistance conversation DB
│   │
│   ├── Agents/
│   │   └── AgentRunner.php               # Runner : résout tools, structured output, memory
│   │
│   ├── Messages/
│   │   ├── Message.php                   # Value object message (role + content)
│   │   └── MessageCollection.php         # Collection de messages
│   │
│   ├── Responses/
│   │   ├── TextResponse.php              # Réponse LLM standard
│   │   ├── StreamedResponse.php          # Réponse SSE streamed
│   │   ├── EmbeddingResponse.php         # Réponse embedding
│   │   ├── ImageResponse.php             # Réponse génération image
│   │   ├── AudioResponse.php             # Réponse transcription Whisper
│   │   └── StructuredResponse.php        # Réponse structured output
│   │
│   ├── Tools/
│   │   ├── Tool.php                      # Base class Tool
│   │   └── ToolRegistry.php              # Registre des tools disponibles
│   │
│   ├── Events/
│   │   ├── RequestSent.php
│   │   ├── ResponseReceived.php
│   │   ├── TokensUsed.php
│   │   └── StreamChunkReceived.php
│   │
│   ├── Enums/
│   │   ├── Model.php                     # Enum des modèles Infomaniak
│   │   ├── ImageModel.php                # Enum SDXL / Flux
│   │   └── Role.php                      # Enum user / assistant / system
│   │
│   ├── Exceptions/
│   │   ├── EuriaException.php
│   │   ├── AuthenticationException.php
│   │   ├── RateLimitException.php
│   │   └── ModelNotSupportedException.php
│   │
│   ├── Testing/
│   │   ├── EuriaFake.php                 # Fake complet style AI::fake()
│   │   ├── FakeTextResponse.php
│   │   ├── FakeStreamResponse.php
│   │   ├── FakeEmbeddingResponse.php
│   │   ├── FakeImageResponse.php
│   │   └── FakeAudioResponse.php
│   │
│   └── Console/
│       ├── MakeAgentCommand.php          # make:euria-agent
│       ├── ListModelsCommand.php         # euria:models
│       └── TestConnectionCommand.php     # euria:test
│
├── stubs/
│   ├── agent.stub
│   └── agent.structured.stub
│
├── tests/
│   ├── Pest.php                          # Bootstrap Pest
│   ├── TestCase.php
│   │
│   ├── Unit/
│   │   ├── ClientTest.php
│   │   ├── TextDriverTest.php
│   │   ├── EmbeddingDriverTest.php
│   │   ├── ImageDriverTest.php
│   │   ├── AudioDriverTest.php
│   │   ├── StreamDriverTest.php
│   │   ├── MessageTest.php
│   │   └── ToolTest.php
│   │
│   └── Feature/
│       ├── AgentTest.php
│       ├── ConversationTest.php
│       ├── StructuredOutputTest.php
│       ├── FakeTest.php
│       ├── EventsTest.php
│       └── ArtisanCommandsTest.php
│
├── .github/
│   └── workflows/
│       ├── tests.yml                     # Tests + PHPStan + Pint
│       └── release.yml                   # Release auto Packagist via tag
│
├── .gitattributes
├── .gitignore
├── CHANGELOG.md
├── CONTRIBUTING.md
├── LICENSE.md
├── README.md
├── composer.json
├── functions.php                         # helper euria()
├── phpunit.xml.dist
└── pint.json
```

---

## 5. composer.json — manifeste Composer

```json
{
    "name": "martin-lechene/laravel-euria",
    "description": "Laravel package for Infomaniak AI Services (Euria) — LLM, Embeddings, Images, Audio, Streaming",
    "keywords": ["laravel", "ai", "infomaniak", "euria", "llm", "embeddings", "whisper", "sdxl"],
    "license": "MIT",
    "authors": [
        {
            "name": "Martin Lécheně",
            "email": "your@email.com"
        }
    ],
    "require": {
        "php": "^8.1",
        "illuminate/contracts": "^10.0|^11.0|^12.0|^13.0",
        "illuminate/support": "^10.0|^11.0|^12.0|^13.0",
        "illuminate/http": "^10.0|^11.0|^12.0|^13.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "require-dev": {
        "laravel/framework": "^10.0|^11.0|^12.0|^13.0",
        "orchestra/testbench": "^8.0|^9.0|^10.0",
        "pestphp/pest": "^2.0|^3.0",
        "pestphp/pest-plugin-laravel": "^2.0|^3.0",
        "phpstan/phpstan": "^1.10|^2.0",
        "phpstan/phpstan-phpunit": "^1.3",
        "nunomaduro/larastan": "^2.0|^3.0",
        "laravel/pint": "^1.0",
        "mockery/mockery": "^1.6"
    },
    "autoload": {
        "psr-4": {
            "MartinLechene\\Euria\\": "src/"
        },
        "files": [
            "functions.php"
        ]
    },
    "autoload-dev": {
        "psr-4": {
            "MartinLechene\\Euria\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "MartinLechene\\Euria\\EuriaServiceProvider"
            ],
            "aliases": {
                "Euria": "MartinLechene\\Euria\\EuriaFacade"
            }
        }
    },
    "scripts": {
        "test": "pest",
        "test:coverage": "pest --coverage",
        "analyse": "phpstan analyse",
        "format": "pint"
    },
    "config": {
        "sort-packages": true
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## 6. Service Provider

```php
// src/EuriaServiceProvider.php
namespace MartinLechene\Euria;

use Illuminate\Support\ServiceProvider;
use MartinLechene\Euria\Console\MakeAgentCommand;
use MartinLechene\Euria\Console\ListModelsCommand;
use MartinLechene\Euria\Console\TestConnectionCommand;

class EuriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/euria.php', 'euria');

        $this->app->singleton(EuriaManager::class, function ($app) {
            return new EuriaManager($app);
        });

        $this->app->alias(EuriaManager::class, 'euria');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__.'/../config/euria.php' => config_path('euria.php'),
            ], 'euria-config');

            // Migrations
            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'euria-migrations');

            // Stubs
            $this->publishes([
                __DIR__.'/../stubs/' => base_path('stubs/euria'),
            ], 'euria-stubs');

            // Commandes Artisan
            $this->commands([
                MakeAgentCommand::class,
                ListModelsCommand::class,
                TestConnectionCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

---

## 7. Configuration `config/euria.php`

```php
// config/euria.php
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
        'text'       => env('EURIA_DEFAULT_TEXT_MODEL', 'mixtral'),
        'embedding'  => env('EURIA_DEFAULT_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'image'      => env('EURIA_DEFAULT_IMAGE_MODEL', 'sdxl'),
        'audio'      => env('EURIA_DEFAULT_AUDIO_MODEL', 'whisper-1'),
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
        'default_format' => env('EURIA_IMAGE_FORMAT', 'square'),  // square | portrait | landscape
        'default_count'  => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistance des conversations
    |--------------------------------------------------------------------------
    */
    'conversations' => [
        'table'   => 'euria_conversations',
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
```

### Variables `.env` complètes

```dotenv
# Auth Infomaniak (requis)
INFOMANIAK_API_TOKEN=your_oauth2_api_token_here

# Base URL (optionnel, pour proxy ou dev local)
INFOMANIAK_AI_BASE_URL=https://api.infomaniak.com/1/ai

# Modèles par défaut (optionnel)
EURIA_DEFAULT_TEXT_MODEL=mixtral
EURIA_DEFAULT_EMBEDDING_MODEL=text-embedding-3-small
EURIA_DEFAULT_IMAGE_MODEL=sdxl
EURIA_DEFAULT_AUDIO_MODEL=whisper-1

# Timeout HTTP
EURIA_TIMEOUT=60

# Format image par défaut : square | portrait | landscape
EURIA_IMAGE_FORMAT=square

# Activer les events Laravel
EURIA_EVENTS_ENABLED=true
```

---

## 8. Authentification — token défaut + override par appel

### Principe

Le package utilise le token de `config('euria.api_token')` par défaut. Chaque appel peut être surchargé via `->withToken('autre_token')` pour le support multi-org / multi-tenant.

```php
// Utilisation standard (token .env)
Euria::text('Bonjour !');

// Override par appel (autre organisation)
Euria::withToken('tok_org2_xxx')->text('Bonjour !');

// Chaînage fluent complet
Euria::withToken('tok_org2_xxx')
    ->model('llama-3')
    ->timeout(120)
    ->text('Bonjour !');
```

### Implémentation `InfomaniakHttpClient`

```php
// src/Client/InfomaniakHttpClient.php
namespace MartinLechene\Euria\Client;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MartinLechene\Euria\Events\RequestSent;
use MartinLechene\Euria\Events\ResponseReceived;
use MartinLechene\Euria\Exceptions\AuthenticationException;
use MartinLechene\Euria\Exceptions\RateLimitException;
use MartinLechene\Euria\Exceptions\EuriaException;

class InfomaniakHttpClient
{
    protected string $token;
    protected string $baseUrl;
    protected int $timeout;
    protected Client $guzzle;

    public function __construct(
        string $token,
        string $baseUrl,
        int $timeout = 60,
    ) {
        $this->token   = $token;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        $this->guzzle = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $this->timeout,
        ]);
    }

    public function withToken(string $token): static
    {
        $clone        = clone $this;
        $clone->token = $token;
        return $clone;
    }

    public function post(string $endpoint, array $payload): array
    {
        event(new RequestSent($endpoint, $payload));

        try {
            $response = $this->guzzle->post($endpoint, [
                RequestOptions::JSON    => $payload,
                RequestOptions::HEADERS => $this->headers(),
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->handleClientException($e);
        }

        $data = json_decode((string) $response->getBody(), true);

        event(new ResponseReceived($endpoint, $data));

        return $data;
    }

    public function postStream(string $endpoint, array $payload): \Generator
    {
        event(new RequestSent($endpoint, $payload));

        $response = $this->guzzle->post($endpoint, [
            RequestOptions::JSON    => array_merge($payload, ['stream' => true]),
            RequestOptions::HEADERS => $this->headers(),
            RequestOptions::STREAM  => true,
        ]);

        $body = $response->getBody();

        while (!$body->eof()) {
            $line = trim($body->read(4096));

            if (str_starts_with($line, 'data: ')) {
                $json = substr($line, 6);
                if ($json === '[DONE]') break;
                $chunk = json_decode($json, true);
                event(new StreamChunkReceived($chunk));
                yield $chunk;
            }
        }
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    protected function handleClientException(\GuzzleHttp\Exception\ClientException $e): never
    {
        $code = $e->getResponse()->getStatusCode();
        match (true) {
            $code === 401 => throw new AuthenticationException('Token Infomaniak invalide ou expiré.', $code, $e),
            $code === 429 => throw new RateLimitException('Rate limit dépassé (60 req/min).', $code, $e),
            default       => throw new EuriaException($e->getMessage(), $code, $e),
        };
    }
}
```

---

## 9. Client HTTP Infomaniak

### `EuriaManager` — Driver Manager

```php
// src/EuriaManager.php
namespace MartinLechene\Euria;

use Illuminate\Support\Manager;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\TextDriver;
use MartinLechene\Euria\Drivers\EmbeddingDriver;
use MartinLechene\Euria\Drivers\ImageDriver;
use MartinLechene\Euria\Drivers\AudioDriver;
use MartinLechene\Euria\Drivers\StreamDriver;

class EuriaManager extends Manager
{
    protected ?string $overrideToken = null;
    protected ?string $overrideModel = null;
    protected ?int    $overrideTimeout = null;

    // --- Auth override fluent ---

    public function withToken(string $token): static
    {
        $this->overrideToken = $token;
        return $this;
    }

    public function model(string $model): static
    {
        $this->overrideModel = $model;
        return $this;
    }

    public function timeout(int $seconds): static
    {
        $this->overrideTimeout = $seconds;
        return $this;
    }

    // --- Client factory ---

    protected function makeClient(): InfomaniakHttpClient
    {
        $client = new InfomaniakHttpClient(
            token:   $this->overrideToken ?? config('euria.api_token'),
            baseUrl: config('euria.base_url'),
            timeout: $this->overrideTimeout ?? config('euria.timeout', 60),
        );
        // Reset overrides après usage
        $this->overrideToken   = null;
        $this->overrideTimeout = null;
        return $client;
    }

    // --- API surface (méthodes publiques) ---

    public function text(string $prompt, array $options = []): Responses\TextResponse
    {
        return (new TextDriver($this->makeClient()))->complete($prompt, $this->overrideModel, $options);
    }

    public function stream(string $prompt, array $options = []): \Generator
    {
        return (new StreamDriver($this->makeClient()))->stream($prompt, $this->overrideModel, $options);
    }

    public function embed(string|array $input, array $options = []): Responses\EmbeddingResponse
    {
        return (new EmbeddingDriver($this->makeClient()))->embed($input, $this->overrideModel, $options);
    }

    public function image(string $prompt, array $options = []): Responses\ImageResponse
    {
        return (new ImageDriver($this->makeClient()))->generate($prompt, $this->overrideModel, $options);
    }

    public function transcribe(string $audioPath, array $options = []): Responses\AudioResponse
    {
        return (new AudioDriver($this->makeClient()))->transcribe($audioPath, $this->overrideModel, $options);
    }

    public function getDefaultDriver(): string
    {
        return 'text';
    }
}
```

---

## 10. Capacité 1 — LLM Text (Chat / Completion)

### `TextDriver`

```php
// src/Drivers/TextDriver.php
namespace MartinLechene\Euria\Drivers;

use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Events\TokensUsed;
use MartinLechene\Euria\Responses\TextResponse;

class TextDriver
{
    public function __construct(
        protected InfomaniakHttpClient $client,
    ) {}

    public function complete(
        string $prompt,
        ?string $model = null,
        array $options = [],
        array $messages = [],
        array $tools = [],
        ?array $schema = null,
    ): TextResponse {
        $model ??= config('euria.defaults.text', 'mixtral');

        // Format compatible OpenAI
        $payload = array_merge([
            'model'    => $model,
            'messages' => empty($messages)
                ? [['role' => 'user', 'content' => $prompt]]
                : $messages,
        ], $options);

        // Structured output via JSON schema
        if ($schema !== null) {
            $payload['response_format'] = [
                'type'        => 'json_schema',
                'json_schema' => ['schema' => $schema],
            ];
        }

        // Function calling / tools
        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        $data = $this->client->post('/openai/chat/completions', $payload);

        // Dispatch event tokens
        if (isset($data['usage'])) {
            event(new TokensUsed(
                promptTokens:     $data['usage']['prompt_tokens'] ?? 0,
                completionTokens: $data['usage']['completion_tokens'] ?? 0,
                totalTokens:      $data['usage']['total_tokens'] ?? 0,
                model:            $model,
            ));
        }

        return new TextResponse($data);
    }
}
```

### `TextResponse`

```php
// src/Responses/TextResponse.php
namespace MartinLechene\Euria\Responses;

use Stringable;

class TextResponse implements Stringable
{
    public readonly string $text;
    public readonly string $model;
    public readonly string $finishReason;
    public readonly array  $usage;
    public readonly array  $raw;

    public function __construct(array $data)
    {
        $this->raw          = $data;
        $this->model        = $data['model'] ?? '';
        $this->text         = $data['choices'][0]['message']['content'] ?? '';
        $this->finishReason = $data['choices'][0]['finish_reason'] ?? '';
        $this->usage        = $data['usage'] ?? [];
    }

    public function __toString(): string
    {
        return $this->text;
    }

    public function toolCalls(): array
    {
        return $this->raw['choices'][0]['message']['tool_calls'] ?? [];
    }
}
```

---

## 11. Capacité 2 — Streaming SSE

### `StreamDriver`

```php
// src/Drivers/StreamDriver.php
namespace MartinLechene\Euria\Drivers;

use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Events\StreamChunkReceived;

class StreamDriver
{
    public function __construct(
        protected InfomaniakHttpClient $client,
    ) {}

    public function stream(
        string $prompt,
        ?string $model = null,
        array $options = [],
        array $messages = [],
    ): \Generator {
        $model ??= config('euria.defaults.text', 'mixtral');

        $payload = array_merge([
            'model'    => $model,
            'messages' => empty($messages)
                ? [['role' => 'user', 'content' => $prompt]]
                : $messages,
        ], $options);

        foreach ($this->client->postStream('/openai/chat/completions', $payload) as $chunk) {
            $content = $chunk['choices'][0]['delta']['content'] ?? null;
            if ($content !== null) {
                yield $content;
            }
        }
    }
}
```

### Usage dans une route Laravel

```php
// routes/web.php
use MartinLechene\Euria\EuriaFacade as Euria;

Route::get('/stream', function () {
    return response()->stream(function () {
        foreach (Euria::stream('Explique la souveraineté numérique') as $chunk) {
            echo "data: {$chunk}\n\n";
            ob_flush();
            flush();
        }
    }, 200, [
        'Content-Type'  => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',
    ]);
});
```

---

## 12. Capacité 3 — Function Calling / Tool Use

### Classe `Tool`

```php
// src/Tools/Tool.php
namespace MartinLechene\Euria\Tools;

abstract class Tool
{
    abstract public function name(): string;
    abstract public function description(): string;
    abstract public function parameters(): array;   // JSON Schema

    public function handle(array $arguments): mixed
    {
        return null;
    }

    public function toArray(): array
    {
        return [
            'type'     => 'function',
            'function' => [
                'name'        => $this->name(),
                'description' => $this->description(),
                'parameters'  => $this->parameters(),
            ],
        ];
    }
}
```

### Exemple d'outil concret

```php
// app/Ai/Tools/GetWeatherTool.php
use MartinLechene\Euria\Tools\Tool;

class GetWeatherTool extends Tool
{
    public function name(): string { return 'get_weather'; }

    public function description(): string { return 'Obtient la météo actuelle pour une ville.'; }

    public function parameters(): array {
        return [
            'type'       => 'object',
            'properties' => [
                'city' => ['type' => 'string', 'description' => 'Nom de la ville'],
            ],
            'required' => ['city'],
        ];
    }

    public function handle(array $arguments): mixed {
        return ['temperature' => 22, 'city' => $arguments['city'], 'condition' => 'Ensoleillé'];
    }
}
```

### Usage avec tools dans un Agent

```php
$response = Euria::withTools([new GetWeatherTool])->text('Quel temps fait-il à Lyon ?');

foreach ($response->toolCalls() as $call) {
    $tool   = resolve($call['function']['name']);
    $result = $tool->handle(json_decode($call['function']['arguments'], true));
    // Boucle de continuation (multi-turn tool calling)
}
```

---

## 13. Capacité 4 — Embeddings

### `EmbeddingDriver`

```php
// src/Drivers/EmbeddingDriver.php
namespace MartinLechene\Euria\Drivers;

use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Responses\EmbeddingResponse;

class EmbeddingDriver
{
    public function __construct(
        protected InfomaniakHttpClient $client,
    ) {}

    public function embed(
        string|array $input,
        ?string $model = null,
        array $options = [],
    ): EmbeddingResponse {
        $model ??= config('euria.defaults.embedding');

        $data = $this->client->post('/openai/embeddings', array_merge([
            'input' => $input,
            'model' => $model,
        ], $options));

        return new EmbeddingResponse($data);
    }
}
```

### `EmbeddingResponse`

```php
// src/Responses/EmbeddingResponse.php
namespace MartinLechene\Euria\Responses;

class EmbeddingResponse
{
    public readonly array $embeddings;
    public readonly array $usage;

    public function __construct(array $data)
    {
        $this->embeddings = array_map(
            fn ($item) => $item['embedding'],
            $data['data'] ?? []
        );
        $this->usage = $data['usage'] ?? [];
    }

    public function first(): array
    {
        return $this->embeddings[0] ?? [];
    }

    public function cosineSimilarity(array $a, array $b): float
    {
        $dot    = array_sum(array_map(fn ($x, $y) => $x * $y, $a, $b));
        $normA  = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $a)));
        $normB  = sqrt(array_sum(array_map(fn ($x) => $x ** 2, $b)));
        return $normA && $normB ? $dot / ($normA * $normB) : 0.0;
    }
}
```

---

## 14. Capacité 5 — Image Generation (SDXL / Flux)

### `ImageDriver`

```php
// src/Drivers/ImageDriver.php
namespace MartinLechene\Euria\Drivers;

use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Responses\ImageResponse;

class ImageDriver
{
    public function __construct(
        protected InfomaniakHttpClient $client,
    ) {}

    public function generate(
        string $prompt,
        ?string $model = null,
        array $options = [],
    ): ImageResponse {
        $model ??= config('euria.defaults.image', 'sdxl');

        $data = $this->client->post('/openai/images/generations', array_merge([
            'prompt' => $prompt,
            'model'  => $model,
            'n'      => $options['count'] ?? config('euria.image.default_count', 1),
            'size'   => $options['format'] ?? config('euria.image.default_format', 'square'),
        ], $options));

        return new ImageResponse($data);
    }
}
```

### `ImageResponse`

```php
// src/Responses/ImageResponse.php
namespace MartinLechene\Euria\Responses;

class ImageResponse
{
    public readonly array $images;

    public function __construct(array $data)
    {
        $this->images = array_map(
            fn ($item) => $item['url'] ?? $item['b64_json'] ?? null,
            $data['data'] ?? []
        );
    }

    public function first(): ?string
    {
        return $this->images[0] ?? null;
    }

    public function all(): array
    {
        return $this->images;
    }
}
```

---

## 15. Capacité 6 — Audio Transcription (Whisper STT)

### `AudioDriver`

```php
// src/Drivers/AudioDriver.php
namespace MartinLechene\Euria\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Responses\AudioResponse;

class AudioDriver
{
    public function __construct(
        protected InfomaniakHttpClient $client,
    ) {}

    public function transcribe(
        string $audioPath,
        ?string $model = null,
        array $options = [],
    ): AudioResponse {
        $model ??= config('euria.defaults.audio', 'whisper-1');

        // Whisper nécessite multipart/form-data
        $guzzle = new Client([
            'base_uri' => config('euria.base_url'),
            'timeout'  => config('euria.timeout', 60),
        ]);

        $response = $guzzle->post('/openai/audio/transcriptions', [
            RequestOptions::MULTIPART => [
                ['name' => 'model', 'contents' => $model],
                ['name' => 'file',  'contents' => fopen($audioPath, 'r'), 'filename' => basename($audioPath)],
                ...(isset($options['language']) ? [['name' => 'language', 'contents' => $options['language']]] : []),
            ],
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer '.config('euria.api_token'),
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return new AudioResponse($data);
    }
}
```

### `AudioResponse`

```php
// src/Responses/AudioResponse.php
namespace MartinLechene\Euria\Responses;

use Stringable;

class AudioResponse implements Stringable
{
    public readonly string $text;
    public readonly ?string $language;
    public readonly ?float  $duration;

    public function __construct(array $data)
    {
        $this->text     = $data['text'] ?? '';
        $this->language = $data['language'] ?? null;
        $this->duration = $data['duration'] ?? null;
    }

    public function __toString(): string
    {
        return $this->text;
    }
}
```

---

## 16. Facade `Euria::` et helper `euria()`

### Facade

```php
// src/EuriaFacade.php
namespace MartinLechene\Euria;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \MartinLechene\Euria\Responses\TextResponse text(string $prompt, array $options = [])
 * @method static \Generator stream(string $prompt, array $options = [])
 * @method static \MartinLechene\Euria\Responses\EmbeddingResponse embed(string|array $input, array $options = [])
 * @method static \MartinLechene\Euria\Responses\ImageResponse image(string $prompt, array $options = [])
 * @method static \MartinLechene\Euria\Responses\AudioResponse transcribe(string $audioPath, array $options = [])
 * @method static static withToken(string $token)
 * @method static static model(string $model)
 * @method static static timeout(int $seconds)
 * @see \MartinLechene\Euria\EuriaManager
 */
class EuriaFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EuriaManager::class;
    }
}
```

### Helper global

```php
// functions.php
use MartinLechene\Euria\EuriaManager;

if (! function_exists('euria')) {
    /**
     * Accès global au manager Euria.
     * @return EuriaManager
     */
    function euria(): EuriaManager
    {
        return app(EuriaManager::class);
    }
}
```

### Usage

```php
// Via Facade
use MartinLechene\Euria\EuriaFacade as Euria;

$response = Euria::text('Bonjour Euria !');
$response = Euria::withToken('tok_xxx')->model('llama-3')->text('Salut');

// Via helper
$response = euria()->text('Bonjour !');
$image    = euria()->image('Un paysage alpin en été');
$audio    = euria()->transcribe('/path/to/audio.mp3');
```

---

## 17. Système d'Agents (style laravel/ai)

Le package reproduit fidèlement le système d'Agents de `laravel/ai` — contrats PHP, trait `Promptable`, mémoire de conversation, structured output — mais en utilisant le client Infomaniak.

### Contrats

```php
// src/Contracts/Agent.php
namespace MartinLechene\Euria\Contracts;

use Stringable;

interface Agent
{
    public function instructions(): Stringable|string;
}

// src/Contracts/Conversational.php
interface Conversational
{
    public function messages(): iterable;
}

// src/Contracts/HasTools.php
interface HasTools
{
    public function tools(): iterable;
}

// src/Contracts/HasStructuredOutput.php
interface HasStructuredOutput
{
    public function schema(): array;  // JSON Schema array
}
```

### Trait `Promptable`

```php
// src/Concerns/Promptable.php
namespace MartinLechene\Euria\Concerns;

use MartinLechene\Euria\Agents\AgentRunner;

trait Promptable
{
    public static function make(mixed ...$args): static
    {
        return app(static::class, $args);
    }

    public function prompt(
        string $message,
        ?string $model = null,
        int $timeout = 60,
        array $attachments = [],
    ): mixed {
        return app(AgentRunner::class)->run($this, $message, $model, $timeout, $attachments);
    }

    public function stream(string $message, ?string $model = null): \Generator
    {
        return app(AgentRunner::class)->stream($this, $message, $model);
    }

    public function queue(string $message): \Illuminate\Foundation\Bus\PendingDispatch
    {
        return app(AgentRunner::class)->queue($this, $message);
    }
}
```

### Exemple d'Agent complet

```php
// app/Ai/Agents/SupportBot.php
namespace App\Ai\Agents;

use MartinLechene\Euria\Concerns\Promptable;
use MartinLechene\Euria\Concerns\RemembersConversations;
use MartinLechene\Euria\Contracts\Agent;
use MartinLechene\Euria\Contracts\Conversational;
use MartinLechene\Euria\Contracts\HasStructuredOutput;
use MartinLechene\Euria\Contracts\HasTools;

class SupportBot implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        return 'Tu es un assistant support souverain hébergé en Suisse par Infomaniak.
                Tu réponds toujours en français, avec concision et bienveillance.';
    }

    public function tools(): iterable
    {
        return [
            new \App\Ai\Tools\SearchKnowledgeBase,
            new \App\Ai\Tools\CreateSupportTicket,
        ];
    }

    public function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'answer'     => ['type' => 'string'],
                'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                'sources'    => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
            'required' => ['answer', 'confidence'],
        ];
    }
}
```

### Usage

```php
// Utilisation simple
$response = (new SupportBot)->prompt('Comment résilier mon abonnement ?');
echo $response; // affiche le texte

// Avec mémoire de conversation
$response = (new SupportBot)->forUser($user)->prompt('Bonjour !');
$id       = $response->conversationId;

// Continuer une conversation
$response = (new SupportBot)->continue($id, as: $user)->prompt('Et pour les remboursements ?');

// Structured output
$response = (new SupportBot)->prompt('Analyse ce ticket...');
echo $response['answer'];
echo $response['confidence'];

// Streaming
foreach ((new SupportBot)->stream('Explique la politique de confidentialité') as $chunk) {
    echo $chunk;
}
```

---

## 18. Events Laravel

### Liste des events

```php
// src/Events/RequestSent.php
namespace MartinLechene\Euria\Events;

class RequestSent
{
    public function __construct(
        public readonly string $endpoint,
        public readonly array  $payload,
        public readonly float  $timestamp = LARAVEL_START,
    ) {}
}

// src/Events/ResponseReceived.php
class ResponseReceived
{
    public function __construct(
        public readonly string $endpoint,
        public readonly array  $response,
        public readonly float  $duration,   // secondes
    ) {}
}

// src/Events/TokensUsed.php
class TokensUsed
{
    public function __construct(
        public readonly int    $promptTokens,
        public readonly int    $completionTokens,
        public readonly int    $totalTokens,
        public readonly string $model,
    ) {}
}

// src/Events/StreamChunkReceived.php
class StreamChunkReceived
{
    public function __construct(
        public readonly string $content,
        public readonly array  $raw,
    ) {}
}
```

### Écoute des events (exemple)

```php
// app/Providers/EventServiceProvider.php
use MartinLechene\Euria\Events\TokensUsed;

Event::listen(TokensUsed::class, function (TokensUsed $event) {
    // Comptabiliser les tokens utilisés
    DB::table('ai_usage')->insert([
        'model'             => $event->model,
        'prompt_tokens'     => $event->promptTokens,
        'completion_tokens' => $event->completionTokens,
        'total_tokens'      => $event->totalTokens,
        'created_at'        => now(),
    ]);
});
```

---

## 19. EuriaFake — Testing Layer complet

### `EuriaFake`

```php
// src/Testing/EuriaFake.php
namespace MartinLechene\Euria\Testing;

use MartinLechene\Euria\EuriaManager;
use MartinLechene\Euria\Responses\TextResponse;
use MartinLechene\Euria\Responses\EmbeddingResponse;
use MartinLechene\Euria\Responses\ImageResponse;
use MartinLechene\Euria\Responses\AudioResponse;
use PHPUnit\Framework\Assert;

class EuriaFake extends EuriaManager
{
    protected array $textResponses = [];
    protected array $imageResponses = [];
    protected array $embeddingResponses = [];
    protected array $audioResponses = [];
    protected array $recordedCalls = [];

    public function fakeText(string $text, array $extra = []): static
    {
        $this->textResponses[] = new TextResponse(array_merge([
            'model'   => 'fake-model',
            'choices' => [['message' => ['content' => $text], 'finish_reason' => 'stop']],
            'usage'   => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ], $extra));
        return $this;
    }

    public function fakeImage(string $url): static
    {
        $this->imageResponses[] = new ImageResponse(['data' => [['url' => $url]]]);
        return $this;
    }

    public function fakeEmbedding(array $vector): static
    {
        $this->embeddingResponses[] = new EmbeddingResponse(['data' => [['embedding' => $vector]], 'usage' => []]);
        return $this;
    }

    public function fakeAudio(string $transcription): static
    {
        $this->audioResponses[] = new AudioResponse(['text' => $transcription]);
        return $this;
    }

    public function text(string $prompt, array $options = []): TextResponse
    {
        $this->recordedCalls[] = ['method' => 'text', 'prompt' => $prompt, 'options' => $options];
        return array_shift($this->textResponses) ?? new TextResponse([
            'model'   => 'fake-model',
            'choices' => [['message' => ['content' => 'Fake response'], 'finish_reason' => 'stop']],
            'usage'   => ['prompt_tokens' => 5, 'completion_tokens' => 10, 'total_tokens' => 15],
        ]);
    }

    public function image(string $prompt, array $options = []): ImageResponse
    {
        $this->recordedCalls[] = ['method' => 'image', 'prompt' => $prompt];
        return array_shift($this->imageResponses) ?? new ImageResponse(['data' => [['url' => 'https://fake.url/image.png']]]);
    }

    public function embed(string|array $input, array $options = []): EmbeddingResponse
    {
        $this->recordedCalls[] = ['method' => 'embed', 'input' => $input];
        return array_shift($this->embeddingResponses) ?? new EmbeddingResponse(['data' => [['embedding' => array_fill(0, 1536, 0.1)]], 'usage' => []]);
    }

    public function transcribe(string $audioPath, array $options = []): AudioResponse
    {
        $this->recordedCalls[] = ['method' => 'transcribe', 'path' => $audioPath];
        return array_shift($this->audioResponses) ?? new AudioResponse(['text' => 'Fake transcription']);
    }

    // --- Assertions ---

    public function assertTextCalled(int $times = 1): void
    {
        $count = count(array_filter($this->recordedCalls, fn ($c) => $c['method'] === 'text'));
        Assert::assertSame($times, $count, "Expected text() to be called {$times} times, got {$count}.");
    }

    public function assertPromptContains(string $needle): void
    {
        $found = array_filter($this->recordedCalls, fn ($c) => isset($c['prompt']) && str_contains($c['prompt'], $needle));
        Assert::assertNotEmpty($found, "No prompt containing \"{$needle}\" was sent to Euria.");
    }

    public function assertImageCalled(int $times = 1): void
    {
        $count = count(array_filter($this->recordedCalls, fn ($c) => $c['method'] === 'image'));
        Assert::assertSame($times, $count);
    }

    public function assertNothingCalled(): void
    {
        Assert::assertEmpty($this->recordedCalls, 'Expected no calls to Euria, but some were recorded.');
    }

    public function recordedCalls(): array
    {
        return $this->recordedCalls;
    }
}
```

### Binding du Fake dans les tests

```php
// src/EuriaFacade.php — méthode fake()
public static function fake(): \MartinLechene\Euria\Testing\EuriaFake
{
    $fake = new \MartinLechene\Euria\Testing\EuriaFake(app());
    app()->instance(EuriaManager::class, $fake);
    return $fake;
}
```

### Usage dans les tests Pest

```php
use MartinLechene\Euria\EuriaFacade as Euria;

it('génère une réponse texte', function () {
    $fake = Euria::fake();
    $fake->fakeText('Bonjour depuis le fake !');

    $response = Euria::text('Dis bonjour');

    expect((string) $response)->toBe('Bonjour depuis le fake !');
    $fake->assertTextCalled(1);
    $fake->assertPromptContains('bonjour');
});

it('génère une image fake', function () {
    $fake = Euria::fake();
    $fake->fakeImage('https://cdn.test/image.png');

    $image = Euria::image('Un paysage suisse');

    expect($image->first())->toBe('https://cdn.test/image.png');
    $fake->assertImageCalled(1);
});
```

---

## 20. Commandes Artisan

### `make:euria-agent`

```php
// src/Console/MakeAgentCommand.php
namespace MartinLechene\Euria\Console;

use Illuminate\Console\GeneratorCommand;

class MakeAgentCommand extends GeneratorCommand
{
    protected $name      = 'make:euria-agent';
    protected $description = 'Créer un nouvel Agent Euria';
    protected $type      = 'Agent';

    protected function getStub(): string
    {
        return $this->option('structured')
            ? __DIR__.'/../../stubs/agent.structured.stub'
            : __DIR__.'/../../stubs/agent.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Ai\Agents';
    }

    protected function getOptions(): array
    {
        return [
            ['structured', 's', \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Créer un agent avec structured output'],
        ];
    }
}
```

### `euria:models`

```php
// src/Console/ListModelsCommand.php
namespace MartinLechene\Euria\Console;

use Illuminate\Console\Command;
use MartinLechene\Euria\EuriaManager;

class ListModelsCommand extends Command
{
    protected $signature   = 'euria:models';
    protected $description = 'Lister les modèles disponibles sur l\'API Infomaniak';

    public function handle(EuriaManager $euria): int
    {
        $this->info('Modèles Infomaniak AI Services disponibles :');
        $this->table(
            ['Capacité', 'Modèle', 'Défaut config'],
            [
                ['LLM Text',    'mixtral, llama-3, deepseek, mistral-7b', config('euria.defaults.text')],
                ['Embeddings',  'text-embedding-3-small',                 config('euria.defaults.embedding')],
                ['Images',      'sdxl, flux',                             config('euria.defaults.image')],
                ['Audio STT',   'whisper-1',                              config('euria.defaults.audio')],
            ]
        );
        return 0;
    }
}
```

### `euria:test`

```php
// src/Console/TestConnectionCommand.php
namespace MartinLechene\Euria\Console;

use Illuminate\Console\Command;
use MartinLechene\Euria\EuriaFacade as Euria;

class TestConnectionCommand extends Command
{
    protected $signature   = 'euria:test';
    protected $description = 'Tester la connexion à l\'API Infomaniak Euria';

    public function handle(): int
    {
        $this->info('Test de connexion Infomaniak AI Services...');

        try {
            $response = Euria::text('Réponds "OK" en un seul mot.');
            $this->info('✅ Connexion réussie !');
            $this->line('Réponse : '.(string) $response);
            $this->line('Modèle  : '.$response->model);
            $this->line('Tokens  : '.$response->usage['total_tokens']);
        } catch (\Exception $e) {
            $this->error('❌ Erreur : '.$e->getMessage());
            return 1;
        }

        return 0;
    }
}
```

---

## 21. Suite de tests Pest PHP

### `tests/Pest.php`

```php
<?php

use MartinLechene\Euria\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
```

### `tests/TestCase.php`

```php
namespace MartinLechene\Euria\Tests;

use MartinLechene\Euria\EuriaServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EuriaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('euria.api_token', 'test-token-fake');
        $app['config']->set('euria.base_url', 'https://api.infomaniak.com/1/ai');
    }
}
```

### Exemple de test Feature — `tests/Feature/AgentTest.php`

```php
<?php

use MartinLechene\Euria\EuriaFacade as Euria;
use MartinLechene\Euria\Contracts\Agent;
use MartinLechene\Euria\Concerns\Promptable;

class SimpleAgent implements Agent {
    use Promptable;
    public function instructions(): string { return 'Tu es un assistant de test.'; }
}

it('peut prompt un agent simple', function () {
    $fake = Euria::fake();
    $fake->fakeText('Réponse de test OK');

    $response = (new SimpleAgent)->prompt('Test ?');

    expect((string) $response)->toBe('Réponse de test OK');
    $fake->assertTextCalled(1);
});

it('peut streamer un agent', function () {
    $fake = Euria::fake();
    // stream retourne les chunks un par un
    $chunks = [];
    foreach ((new SimpleAgent)->stream('Stream test') as $chunk) {
        $chunks[] = $chunk;
    }
    expect($chunks)->not->toBeEmpty();
});
```

### Matrice de tests — couverture complète

| Fichier | Tests |
|---|---|
| `Unit/ClientTest.php` | Auth headers, token override, rate limit exception, 401 exception |
| `Unit/TextDriverTest.php` | Payload format, model sélection, tool_calls extraction |
| `Unit/StreamDriverTest.php` | SSE parsing, chunk yield, [DONE] termination |
| `Unit/EmbeddingDriverTest.php` | Payload, response parsing, cosineSimilarity |
| `Unit/ImageDriverTest.php` | Payload SDXL/Flux, count, format |
| `Unit/AudioDriverTest.php` | Multipart form, transcription text |
| `Feature/AgentTest.php` | Prompt, stream, queue, make() |
| `Feature/ConversationTest.php` | forUser, continue, messages DB |
| `Feature/StructuredOutputTest.php` | schema validation, array access |
| `Feature/FakeTest.php` | fakeText, fakeImage, assertTextCalled, assertPromptContains |
| `Feature/EventsTest.php` | RequestSent dispatched, TokensUsed dispatched |
| `Feature/ArtisanCommandsTest.php` | euria:test OK, euria:models table, make:euria-agent file creation |

---

## 22. Qualité de code — PHPStan level 9 + Laravel Pint

### `phpstan.neon`

```neon
includes:
    - vendor/nunomaduro/larastan/extension.neon

parameters:
    level: 9
    paths:
        - src
    ignoreErrors:
        - '#Call to an undefined method Illuminate\\Contracts\\Foundation\\Application#'
    checkMissingIterableValueType: false
```

### `pint.json`

```json
{
    "preset": "laravel",
    "rules": {
        "concat_space": {"spacing": "one"},
        "ordered_imports": {"sort_algorithm": "alpha"},
        "no_unused_imports": true,
        "single_quote": true
    }
}
```

### `composer.json` scripts complets

```json
"scripts": {
    "test":          "pest",
    "test:coverage": "pest --coverage --min=80",
    "analyse":       "phpstan analyse --memory-limit=512M",
    "format":        "pint",
    "format:check":  "pint --test",
    "quality":       ["@format:check", "@analyse", "@test"]
}
```

---

## 23. CI/CD — GitHub Actions

### `.github/workflows/tests.yml` — Tests + PHPStan + Pint sur chaque PR

```yaml
name: Tests & Quality

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    name: PHP ${{ matrix.php }} / Laravel ${{ matrix.laravel }}
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        php:     ['8.1', '8.2', '8.3', '8.4']
        laravel: ['10.*', '11.*', '12.*', '13.*']
        exclude:
          - php: '8.1'
            laravel: '13.*'  # Laravel 13 requiert PHP 8.2+

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, json, curl
          coverage: xdebug

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" --no-interaction --no-update
          composer update --prefer-dist --no-interaction

      - name: Run Pint (format check)
        run: vendor/bin/pint --test

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --memory-limit=512M

      - name: Run Pest
        run: vendor/bin/pest --coverage --min=80
```

### `.github/workflows/release.yml` — Release auto Packagist via tag

```yaml
name: Release & Packagist

on:
  push:
    tags:
      - 'v*.*.*'

jobs:
  release:
    name: Create GitHub Release
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Create GitHub Release
        uses: softprops/action-gh-release@v1
        with:
          generate_release_notes: true
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}

  packagist:
    name: Notify Packagist
    runs-on: ubuntu-latest
    needs: release

    steps:
      - name: Trigger Packagist update
        run: |
          curl -XPOST -H 'content-type:application/json' \
            'https://packagist.org/api/update-package?username=${{ secrets.PACKAGIST_USERNAME }}&apiToken=${{ secrets.PACKAGIST_TOKEN }}' \
            -d '{"repository":{"url":"https://github.com/martin-lechene/laravel-euria"}}'
```

### Secrets GitHub requis

| Secret | Valeur |
|---|---|
| `PACKAGIST_USERNAME` | Ton username Packagist |
| `PACKAGIST_TOKEN` | Token API Packagist |

---

## 24. Documentation

### README.md — structure

```markdown
# 🇨🇭 Laravel Euria

[![Tests](https://github.com/martin-lechene/laravel-euria/actions/workflows/tests.yml/badge.svg)]
[![Latest Version on Packagist](https://img.shields.io/packagist/v/martin-lechene/laravel-euria.svg)]
[![Total Downloads](https://img.shields.io/packagist/dt/martin-lechene/laravel-euria.svg)]
[![License](https://img.shields.io/packagist/l/martin-lechene/laravel-euria.svg)]

Package Laravel pour l'API Infomaniak AI Services (Euria) — LLM souverain hébergé en Suisse.

## Installation
## Configuration
## Utilisation rapide
## Agents
## Streaming
## Embeddings
## Génération d'images
## Transcription audio
## Function Calling / Tools
## Testing (EuriaFake)
## Events
## Commandes Artisan
## Contributing
## License
```

### Docs Markdown (GitHub Pages / Docsify)

Structure du dossier `docs/` :

```
docs/
├── index.html          # Docsify bootstrap
├── _sidebar.md
├── getting-started.md
├── agents.md
├── streaming.md
├── embeddings.md
├── images.md
├── audio.md
├── tools.md
├── testing.md
├── events.md
├── artisan.md
└── api-reference.md    # PHPDoc auto-généré
```

### Gumroad Product Page — structure

```
Titre        : Laravel Euria — Infomaniak AI SDK for Laravel
Prix         : 0$ (gratuit) ou 9$ (avec docs PDF + exemples)
Description  :
  - Ce que c'est (1 paragraph)
  - Features list (bullets)
  - Code snippet (text)
  - Requirements (PHP/Laravel)
  - Lien GitHub + Packagist
  - Screenshots (README rendered)
  - Support / Issues : GitHub Issues
```

---

## 25. Publication Packagist

### Étapes

1. Créer le repo GitHub : `https://github.com/martin-lechene/laravel-euria`
2. Push le code avec la structure complète + `composer.json`
3. Aller sur `https://packagist.org/packages/submit`
4. Soumettre l'URL du repo GitHub
5. Activer le **GitHub webhook** Packagist pour les mises à jour auto
6. Configurer les secrets GitHub CI/CD (`PACKAGIST_USERNAME`, `PACKAGIST_TOKEN`)
7. Créer le premier tag : `git tag v0.1.0 && git push --tags`
8. Vérifier sur `https://packagist.org/packages/martin-lechene/laravel-euria`

### Versioning SemVer

| Tag | Signification |
|---|---|
| `v0.1.0` | Premier release — LLM Text + Streaming |
| `v0.2.0` | + Embeddings + Images |
| `v0.3.0` | + Whisper STT + Tools |
| `v0.4.0` | + Agents complets + Memory |
| `v1.0.0` | Stable — toutes capacités + tests + docs |

---

## 26. Roadmap de développement (phases)

### Phase 1 — Fondations (semaine 1)

- [ ] Créer le repo GitHub `martin-lechene/laravel-euria`
- [ ] Initialiser `composer.json`, `EuriaServiceProvider`, `EuriaManager`
- [ ] Implémenter `InfomaniakHttpClient` (Guzzle + Bearer)
- [ ] Implémenter `TextDriver` + `TextResponse`
- [ ] Écrire `functions.php` (helper `euria()`)
- [ ] Créer `EuriaFacade`
- [ ] Configurer Pest + `TestCase` + `EuriaFake` de base
- [ ] GitHub Actions `tests.yml`
- [ ] README minimal

### Phase 2 — Toutes les capacités (semaine 2)

- [ ] `StreamDriver` + SSE
- [ ] `EmbeddingDriver` + `EmbeddingResponse`
- [ ] `ImageDriver` + `ImageResponse` (SDXL + Flux)
- [ ] `AudioDriver` + `AudioResponse` (Whisper STT)
- [ ] Events `RequestSent`, `ResponseReceived`, `TokensUsed`, `StreamChunkReceived`
- [ ] Tests unitaires pour chaque driver

### Phase 3 — Agents & Tools (semaine 3)

- [ ] Contrats `Agent`, `Conversational`, `HasTools`, `HasStructuredOutput`
- [ ] Trait `Promptable` + `RemembersConversations`
- [ ] `AgentRunner` complet
- [ ] `ToolRegistry` + `Tool` base class
- [ ] Migrations DB (conversations + messages)
- [ ] Tests Feature Agents

### Phase 4 — DX & Publication (semaine 4)

- [ ] Commandes Artisan `make:euria-agent`, `euria:models`, `euria:test`
- [ ] `EuriaFake` complet avec toutes les assertions
- [ ] PHPStan level 9 passé
- [ ] Pint formatage clean
- [ ] Docs Markdown (GitHub Pages)
- [ ] GitHub Actions `release.yml`
- [ ] Publication Packagist `v1.0.0`
- [ ] Page Gumroad

---

*Fin du Plan A — `martin-lechene/laravel-euria`*
