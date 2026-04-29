<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\ImageDriver;
use MartinLechene\Euria\Responses\ImageResponse;

it('generates image with correct payload', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [['url' => 'https://example.com/image.png']],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    $driver = new ImageDriver($client);
    $response = $driver->generate('A landscape');

    expect($response)->toBeInstanceOf(ImageResponse::class)
        ->and($response->first())->toBe('https://example.com/image.png');
});

it('can return multiple images', function () {
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'data' => [
                ['url' => 'https://example.com/img1.png'],
                ['url' => 'https://example.com/img2.png'],
            ],
        ])),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    $driver = new ImageDriver($client);
    $response = $driver->generate('Multiple images', null, ['count' => 2]);

    expect($response->all())->toHaveCount(2);
});
