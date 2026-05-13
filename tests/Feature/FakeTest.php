<?php

use MartinLechene\Euria\Facades\Euria;

it('fakeText works', function () {
    $fake = Euria::fake();
    $fake->fakeText('Hello Fake');

    $response = Euria::text('Test');
    expect((string) $response)->toBe('Hello Fake');
});

it('fakeImage works', function () {
    $fake = Euria::fake();
    $fake->fakeImage('https://fake.url/image.png');

    $image = Euria::image('Test');
    expect($image->first())->toBe('https://fake.url/image.png');
});

it('assertPromptContains works', function () {
    $fake = Euria::fake();
    $fake->fakeText('Test');

    Euria::text('Say hello to me');
    $fake->assertPromptContains('hello');
});
