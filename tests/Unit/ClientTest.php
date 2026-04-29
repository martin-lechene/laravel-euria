<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Exceptions\AuthenticationException;
use MartinLechene\Euria\Exceptions\RateLimitException;

it('has correct auth headers', function () {
    $client = new InfomaniakHttpClient('test-token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $method = $reflection->getMethod('headers');
    $headers = $method->invoke($client);

    expect($headers['Authorization'])->toBe('Bearer test-token')
        ->and($headers['Content-Type'])->toBe('application/json')
        ->and($headers['Accept'])->toBe('application/json');
});

it('can override token via withToken', function () {
    $client = new InfomaniakHttpClient('original-token', 'https://api.test');
    $newClient = $client->withToken('new-token');

    $reflection = new ReflectionClass($newClient);
    $property = $reflection->getProperty('token');
    $token = $property->getValue($newClient);

    expect($token)->toBe('new-token');
});

it('throws AuthenticationException on 401', function () {
    $mock = new MockHandler([
        new ClientException(
            'Unauthorized',
            new Request('POST', '/test'),
            new Response(401, [], json_encode(['error' => 'Unauthorized']))
        ),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('bad-token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    expect(fn () => $client->post('/test', []))
        ->toThrow(AuthenticationException::class);
});

it('throws RateLimitException on 429', function () {
    $mock = new MockHandler([
        new ClientException(
            'Too Many Requests',
            new Request('POST', '/test'),
            new Response(429, [], json_encode(['error' => 'Too Many Requests']))
        ),
    ]);
    $handlerStack = HandlerStack::create($mock);
    $guzzle = new Client(['handler' => $handlerStack, 'base_uri' => 'https://api.test']);

    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $reflection = new ReflectionClass($client);
    $prop = $reflection->getProperty('guzzle');
    $prop->setAccessible(true);
    $prop->setValue($client, $guzzle);

    expect(fn () => $client->post('/test', []))
        ->toThrow(RateLimitException::class);
});
