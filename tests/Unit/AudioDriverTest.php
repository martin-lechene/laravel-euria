<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\AudioDriver;
use MartinLechene\Euria\Responses\AudioResponse;

it('transcribes audio correctly', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'text' => 'Hello world',
            'language' => 'en',
            'duration' => 2.5,
        ])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    $driver = new AudioDriver($client);
    $response = $driver->transcribe('/tmp/test.mp3');

    expect($response)->toBeInstanceOf(AudioResponse::class)
        ->and((string) $response)->toBe('Hello world')
        ->and($response->language)->toBe('en');
});
