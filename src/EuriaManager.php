<?php

namespace MartinLechene\Euria;

use Illuminate\Support\Manager;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\AudioDriver;
use MartinLechene\Euria\Drivers\EmbeddingDriver;
use MartinLechene\Euria\Drivers\ImageDriver;
use MartinLechene\Euria\Drivers\StreamDriver;
use MartinLechene\Euria\Drivers\TextDriver;
use MartinLechene\Euria\Responses\AudioResponse;
use MartinLechene\Euria\Responses\EmbeddingResponse;
use MartinLechene\Euria\Responses\ImageResponse;
use MartinLechene\Euria\Responses\TextResponse;

class EuriaManager extends Manager
{
    protected ?string $overrideToken = null;

    protected ?string $overrideModel = null;

    protected ?int $overrideTimeout = null;

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

    protected function makeClient(): InfomaniakHttpClient
    {
        $token = $this->overrideToken ?? config('euria.api_token');
        $baseUrl = config('euria.base_url');
        $timeout = $this->overrideTimeout ?? config('euria.timeout', 60);
        
        /** @var string $tokenValue */
        $tokenValue = is_string($token) ? $token : '';
        /** @var string $baseUrlValue */
        $baseUrlValue = is_string($baseUrl) ? $baseUrl : '';
        /** @var int $timeoutValue */
        $timeoutValue = is_int($timeout) ? $timeout : 60;
        
        $client = new InfomaniakHttpClient($tokenValue, $baseUrlValue, $timeoutValue);
        $this->overrideToken   = null;
        $this->overrideModel   = null;
        $this->overrideTimeout = null;
        return $client;
    }

    public function text(string $prompt, array $options = []): TextResponse
    {
        return (new TextDriver($this->makeClient()))->complete($prompt, $this->overrideModel, $options);
    }

    public function stream(string $prompt, array $options = []): \Generator
    {
        return (new StreamDriver($this->makeClient()))->stream($prompt, $this->overrideModel, $options);
    }

    public function embed(string|array $input, array $options = []): EmbeddingResponse
    {
        return (new EmbeddingDriver($this->makeClient()))->embed($input, $this->overrideModel, $options);
    }

    public function image(string $prompt, array $options = []): ImageResponse
    {
        return (new ImageDriver($this->makeClient()))->generate($prompt, $this->overrideModel, $options);
    }

    public function transcribe(string $audioPath, array $options = []): AudioResponse
    {
        return (new AudioDriver($this->makeClient()))->transcribe($audioPath, $this->overrideModel, $options);
    }

    public function getDefaultDriver(): string
    {
        return 'text';
    }
}
