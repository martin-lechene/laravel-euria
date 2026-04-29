<?php

use MartinLechene\Euria\Concerns\Promptable;
use MartinLechene\Euria\Contracts\Agent;
use MartinLechene\Euria\EuriaFacade as Euria;

class SimpleAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a test assistant.';
    }
}

it('can prompt a simple agent', function () {
    $fake = Euria::fake();
    $fake->fakeText('Test response OK');

    $response = (new SimpleAgent)->prompt('Test?');

    expect((string) $response)->toBe('Test response OK');
    $fake->assertTextCalled(1);
});

it('can stream an agent', function () {
    $fake = Euria::fake();

    // Streaming returns a generator - just test it doesn't throw
    $generator = (new SimpleAgent)->stream('Stream test');
    expect($generator)->toBeInstanceOf(Generator::class);
});
