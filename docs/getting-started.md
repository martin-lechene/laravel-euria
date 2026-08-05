# Getting Started

## Installation

```bash
composer require martin-lechene/laravel-euria
```

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=euria-config
```

Set your `.env`:

```env
INFOMANIAK_API_TOKEN=your_oauth2_api_token_here
INFOMANIAK_AI_BASE_URL=https://api.infomaniak.com/1/ai
```

## Quick Usage

```php
use MartinLechene\Euria\Facades\Euria;

$response = Euria::text('Hello Euria!');
echo $response;
```
