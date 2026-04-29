<?php

namespace MartinLechene\Euria\Drivers;

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

        $multipart = [
            ['name' => 'model', 'contents' => $model],
            ['name' => 'file',  'contents' => fopen($audioPath, 'r'), 'filename' => basename($audioPath)],
        ];

        if (isset($options['language'])) {
            $multipart[] = ['name' => 'language', 'contents' => $options['language']];
        }

        $data = $this->client->postMultipart('/openai/audio/transcriptions', $multipart);

        return new AudioResponse($data);
    }
}
