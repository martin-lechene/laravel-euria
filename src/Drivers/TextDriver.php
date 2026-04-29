<?php

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

        $payload = array_merge([
            'model' => $model,
            'messages' => empty($messages)
                ? [['role' => 'user', 'content' => $prompt]]
                : $messages,
        ], $options);

        if ($schema !== null) {
            $payload['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => ['schema' => $schema],
            ];
        }

        if (! empty($tools)) {
            $payload['tools'] = $tools;
        }

        $data = $this->client->post('/openai/chat/completions', $payload);

        if (isset($data['usage']) && is_array($data['usage'])) {
            /** @var array $usage */
            $usage = $data['usage'];
            event(new TokensUsed(
                promptTokens:     (int) ($usage['prompt_tokens'] ?? 0),
                completionTokens: (int) ($usage['completion_tokens'] ?? 0),
                totalTokens:      (int) ($usage['total_tokens'] ?? 0),
                model:            is_string($model) ? $model : '',
            ));
        }

        return new TextResponse($data);
    }
}
