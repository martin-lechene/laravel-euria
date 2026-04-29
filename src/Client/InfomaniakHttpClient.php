<?php

namespace MartinLechene\Euria\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use MartinLechene\Euria\Events\RequestSent;
use MartinLechene\Euria\Events\ResponseReceived;
use MartinLechene\Euria\Events\StreamChunkReceived;
use MartinLechene\Euria\Exceptions\AuthenticationException;
use MartinLechene\Euria\Exceptions\EuriaException;
use MartinLechene\Euria\Exceptions\RateLimitException;

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
        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;

        $this->guzzle = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
        ]);
    }

    public function withToken(string $token): static
    {
        $clone = clone $this;
        $clone->token = $token;

        return $clone;
    }

    public function post(string $endpoint, array $payload): array
    {
        $start = microtime(true);
        event(new RequestSent($endpoint, $payload));

        try {
            $response = $this->guzzle->post(ltrim($endpoint, '/'), [
                RequestOptions::JSON => $payload,
                RequestOptions::HEADERS => $this->headers(),
            ]);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        }

        $duration = microtime(true) - $start;
        /** @var array $data */
        $data = json_decode((string) $response->getBody(), true);

        event(new ResponseReceived($endpoint, $data, $duration));

        return $data;
    }

    public function postStream(string $endpoint, array $payload): \Generator
    {
        event(new RequestSent($endpoint, $payload));

        $response = $this->guzzle->post(ltrim($endpoint, '/'), [
            RequestOptions::JSON => array_merge($payload, ['stream' => true]),
            RequestOptions::HEADERS => $this->headers(),
            RequestOptions::STREAM => true,
        ]);

        $body = $response->getBody();

        while (! $body->eof()) {
            $line = trim($body->read(4096));

            if (str_starts_with($line, 'data: ')) {
                $json = substr($line, 6);
                if ($json === '[DONE]') {
                    break;
                }
                /** @var array|null $chunk */
                $chunk = json_decode($json, true);
                if (is_array($chunk)) {
                    event(new StreamChunkReceived($chunk));
                    $content = $chunk['choices'][0]['delta']['content'] ?? null;
                    if ($content !== null) {
                        yield $content;
                    }
                }
            }
        }
    }

    public function postMultipart(string $endpoint, array $multipart): array
    {
        event(new RequestSent($endpoint, ['multipart' => true]));

        $response = $this->guzzle->post(ltrim($endpoint, '/'), [
            RequestOptions::MULTIPART => $multipart,
            RequestOptions::HEADERS => ['Authorization' => 'Bearer '.$this->token],
        ]);

        /** @var array $data */
        $data = json_decode((string) $response->getBody(), true);

        event(new ResponseReceived($endpoint, $data, 0.0));

        return $data;
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    protected function handleClientException(ClientException $e): never
    {
        $code = $e->getResponse()->getStatusCode();
        match (true) {
            $code === 401 => throw new AuthenticationException('Invalid or expired Infomaniak token.', $code, $e),
            $code === 429 => throw new RateLimitException('Rate limit exceeded (60 req/min).', $code, $e),
            default => throw new EuriaException($e->getMessage(), $code, $e),
        };
    }
}
