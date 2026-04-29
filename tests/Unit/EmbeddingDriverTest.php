<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\EmbeddingDriver;
use MartinLechene\Euria\Responses\EmbeddingResponse;

it('formats embedding payload correctly', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                ['embedding' => [0.1, 0.2, 0.3]],
            ],
            'usage' => ['prompt_tokens' => 5, 'total_tokens' => 5],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    $driver = new EmbeddingDriver($client);
    $response = $driver->embed('Test text');

    expect($response)->toBeInstanceOf(EmbeddingResponse::class)
        ->and($response->first())->toBe([0.1, 0.2, 0.3]);
});

it('calculates cosine similarity', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [['embedding' => [1, 0, 0]]],
            'usage' => [],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    $driver = new EmbeddingDriver($client);
    $response = $driver->embed('Test');
    $embedding = $response->first();

    $similarity = $response->cosineSimilarity([1, 0, 0], [1, 0, 0]);
    expect($similarity)->toBe(1.0);
});
