<?php

use MartinLechene\Euria\Client\InfomaniakHttpClient;
use MartinLechene\Euria\Drivers\StreamDriver;

it('can be instantiated', function () {
    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $driver = new StreamDriver($client);
    expect($driver)->toBeInstanceOf(StreamDriver::class);
});

it('stream method exists', function () {
    $client = new InfomaniakHttpClient('token', 'https://api.test');
    $driver = new StreamDriver($client);
    expect(method_exists($driver, 'stream'))->toBeTrue();
});
