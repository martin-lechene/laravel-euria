<?php

use MartinLechene\Euria\Concerns\Promptable;
use MartinLechene\Euria\Contracts\Agent;
use MartinLechene\Euria\EuriaFacade as Euria;

class SimpleAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Tu es un assistant de test.';
    }
}

it('peut prompt un agent simple', function () {
    $fake = Euria::fake();
    $fake->fakeText('Réponse de test OK');

    $response = (new SimpleAgent)->prompt('Test ?');

    expect((string) $response)->toBe('Réponse de test OK');
    $fake->assertTextCalled(1);
});

it('peut streamer un agent', function () {
    $fake = Euria::fake();

    // Streaming returns a generator - just test it doesn't throw
    $generator = (new SimpleAgent)->stream('Stream test');
    expect($generator)->toBeInstanceOf(Generator::class);
});
