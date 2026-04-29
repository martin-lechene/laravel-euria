<?php

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
        $model ??= config('euria.defaults.embedding', 'text-embedding-3-small');

        $data = $this->client->post('/openai/embeddings', array_merge([
            'input' => $input,
            'model' => $model,
        ], $options));

        return new EmbeddingResponse($data);
    }
}
