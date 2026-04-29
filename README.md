# 🇨🇭 Laravel Euria

[![Tests](https://github.com/martin-lechene/laravel-euria/actions/workflows/tests.yml/badge.svg)](https://github.com/martin-lechene/laravel-euria/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/martin-lechene/laravel-euria.svg)](https://packagist.org/packages/martin-lechene/laravel-euria)
[![Total Downloads](https://img.shields.io/packagist/dt/martin-lechene/laravel-euria.svg)](https://packagist.org/packages/martin-lechene/laravel-euria)
[![License](https://img.shields.io/packagist/l/martin-lechene/laravel-euria.svg)](LICENSE.md)

Package Laravel pour l'API Infomaniak AI Services (Euria) — LLM souverain hébergé en Suisse.

## Installation

```bash
composer require martin-lechene/laravel-euria
```

## Configuration

Publiez la config :

```bash
php artisan vendor:publish --tag=euria-config
```

Puis configurez votre `.env` :

```env
INFOMANIAK_API_TOKEN=your_oauth2_api_token_here
INFOMANIAK_AI_BASE_URL=https://api.infomaniak.com/1/ai
EURIA_DEFAULT_TEXT_MODEL=mixtral
EURIA_DEFAULT_EMBEDDING_MODEL=text-embedding-3-small
EURIA_DEFAULT_IMAGE_MODEL=sdxl
EURIA_DEFAULT_AUDIO_MODEL=whisper-1
EURIA_TIMEOUT=60
EURIA_IMAGE_FORMAT=square
EURIA_EVENTS_ENABLED=true
```

## Utilisation rapide

```php
use MartinLechene\Euria\EuriaFacade as Euria;

// Via Facade
$response = Euria::text('Bonjour Euria !');
echo $response;

// Via helper
$response = euria()->text('Bonjour !');

// Override token (multi-tenant)
$response = Euria::withToken('tok_org2_xxx')->model('llama-3')->text('Salut');

// Streaming
foreach (Euria::stream('Explique la souveraineté numérique') as $chunk) {
    echo $chunk;
}

// Embeddings
$embedding = euria()->embed('Texte à vectoriser')->first();

// Images
$imageUrl = euria()->image('Un paysage alpin en été')->first();

// Transcription audio
$text = euria()->transcribe('/path/to/audio.mp3');
```

## Agents

```php
class SupportBot implements \MartinLechene\Euria\Contracts\Agent
{
    use \MartinLechene\Euria\Concerns\Promptable;

    public function instructions(): string
    {
        return 'Tu es un assistant support souverain hébergé en Suisse.';
    }
}

$response = (new SupportBot)->prompt('Comment résilier mon abonnement ?');
```

## Testing

Le package inclut `EuriaFake` pour tester sans appeler l'API :

```php
use MartinLechene\Euria\EuriaFacade as Euria;

it('génère une réponse texte', function () {
    $fake = Euria::fake();
    $fake->fakeText('Bonjour depuis le fake !');

    $response = Euria::text('Dis bonjour');

    expect((string) $response)->toBe('Bonjour depuis le fake !');
    $fake->assertTextCalled(1);
});
```

## Commandes Artisan

```bash
php artisan euria:test        # Tester la connexion
php artisan euria:models      # Lister les modèles disponibles
php artisan make:euria-agent  # Créer un nouvel Agent
```

## Contributing

Les PRs sont les bienvenues ! Merci de lancer `composer quality` avant de soumettre.

## License

MIT
