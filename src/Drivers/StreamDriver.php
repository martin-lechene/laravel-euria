<?php

namespace MartinLechene\Euria\Drivers;

use MartinLechene\Euria\Client\InfomaniakHttpClient;

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
            'model' => $model,
            'messages' => empty($messages)
                ? [['role' => 'user', 'content' => $prompt]]
                : $messages,
            'stream' => true,
        ], $options);

        foreach ($this->client->postStream('/openai/chat/completions', $payload) as $chunk) {
            /** @var array $chunk */
            $content = $chunk['choices'][0]['delta']['content'] ?? null;
            if ($content !== null) {
                yield $content;
            }
        }
    }
}
