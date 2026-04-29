<?php

use MartinLechene\Euria\EuriaFacade as Euria;
use MartinLechene\Euria\Responses\TextResponse;

it('dispatch RequestSent event', function () {
    $fake = Euria::fake();
    $fake->fakeText('Test');

    $response = Euria::text('Test');
    expect($response)->toBeInstanceOf(TextResponse::class);
});

it('dispatch TokensUsed event', function () {
    $fake = Euria::fake();
    $fake->fakeText('Test');

    $response = Euria::text('Test');
    expect($response)->toBeInstanceOf(TextResponse::class);
});
