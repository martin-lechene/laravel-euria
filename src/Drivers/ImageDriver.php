<?php

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
            'model' => $model,
            'n' => $options['count'] ?? config('euria.image.default_count', 1),
            'size' => $options['format'] ?? config('euria.image.default_format', 'square'),
        ], $options));

        return new ImageResponse($data);
    }
}
