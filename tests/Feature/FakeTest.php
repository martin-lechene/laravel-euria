<?php

use MartinLechene\Euria\EuriaFacade as Euria;

it('fakeText fonctionne', function () {
    $fake = Euria::fake();
    $fake->fakeText('Hello Fake');

    $response = Euria::text('Test');
    expect((string) $response)->toBe('Hello Fake');
});

it('fakeImage fonctionne', function () {
    $fake = Euria::fake();
    $fake->fakeImage('https://fake.url/image.png');

    $image = Euria::image('Test');
    expect($image->first())->toBe('https://fake.url/image.png');
});

it('assertPromptContains fonctionne', function () {
    $fake = Euria::fake();
    $fake->fakeText('Test');

    Euria::text('Dis-moi bonjour');
    $fake->assertPromptContains('bonjour');
});
