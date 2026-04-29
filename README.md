# 🇨🇭 Laravel Euria

[![Tests](https://github.com/martin-lechene/laravel-euria/actions/workflows/tests.yml/badge.svg)](https://github.com/martin-lechene/laravel-euria/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/martin-lechene/laravel-euria.svg)](https://packagist.org/packages/martin-lechene/laravel-euria)
[![Total Downloads](https://img.shields.io/packagist/dt/martin-lechene/laravel-euria.svg)](https://packagist.org/packages/martin-lechene/laravel-euria)
[![License](https://img.shields.io/packagist/l/martin-lechene/laravel-euria.svg)](LICENSE.md)

Laravel package for the Infomaniak AI Services (Euria) API — sovereign LLM hosted in Switzerland.

## Installation

```bash
composer require martin-lechene/laravel-euria
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=euria-config
```

Then configure your `.env`:

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

## Quick Usage

```php
use MartinLechene\Euria\EuriaFacade as Euria;

// Via Facade
$response = Euria::text('Hello Euria!');
echo $response;

// Via helper
$response = euria()->text('Hello!');

// Override token (multi-tenant)
$response = Euria::withToken('tok_org2_xxx')->model('llama-3')->text('Hi');

// Streaming
foreach (Euria::stream('Explain digital sovereignty') as $chunk) {
    echo $chunk;
}

// Embeddings
$embedding = euria()->embed('Text to vectorize')->first();

// Images
$imageUrl = euria()->image('An alpine landscape in summer')->first();

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
        return 'You are a sovereign support assistant hosted in Switzerland.';
    }
}

$response = (new SupportBot)->prompt('How do I cancel my subscription?');
```

## Testing

The package includes `EuriaFake` for testing without calling the API:

```php
use MartinLechene\Euria\EuriaFacade as Euria;

it('generates a text response', function () {
    $fake = Euria::fake();
    $fake->fakeText('Hello from the fake!');

    $response = Euria::text('Say hello');

    expect((string) $response)->toBe('Hello from the fake!');
    $fake->assertTextCalled(1);
});
```

## Artisan Commands

```bash
php artisan euria:test        # Test connection
php artisan euria:models      # List available models
php artisan make:euria-agent  # Create a new Agent
```

## Contributing

PRs are welcome! Please run `composer quality` before submitting.

## License

MIT
