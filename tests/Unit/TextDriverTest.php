<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\TextDriver;
use MartinLechene\Euria\Responses\TextResponse;

it('formats payload correctly', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'model' => 'mixtral',
            'choices' => [['message' => ['content' => 'Hello'], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 10, 'total_tokens' => 15],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    $driver = new TextDriver($client);
    $response = $driver->complete('Test prompt');

    expect($response)->toBeInstanceOf(TextResponse::class)
        ->and((string) $response)->toBe('Hello')
        ->and($response->model)->toBe('mixtral');
});

it('uses default model from config', function () {
    config(['euria.defaults.text' => 'llama-3']);

    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'model' => 'llama-3',
            'choices' => [['message' => ['content' => 'Hi'], 'finish_reason' => 'stop']],
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

    $driver = new TextDriver($client);
    $response = $driver->complete('Test');

    expect($response->model)->toBe('llama-3');
});

it('extracts tool calls from response', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'model' => 'mixtral',
            'choices' => [[
                'message' => [
                    'content' => '',
                    'tool_calls' => [
                        ['function' => ['name' => 'get_weather', 'arguments' => '{"city":"Lyon"}']],
                    ],
                ],
                'finish_reason' => 'tool_calls',
            ]],
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

    $driver = new TextDriver($client);
    $response = $driver->complete('Test');

    expect($response->toolCalls())->toHaveCount(1)
        ->and($response->toolCalls()[0]['function']['name'])->toBe('get_weather');
});
