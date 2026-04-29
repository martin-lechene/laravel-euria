<?php

use MartinLechene\Euria\Concerns\Promptable;
use MartinLechene\Euria\Contracts\Agent;
use MartinLechene\Euria\Contracts\HasStructuredOutput;
use MartinLechene\Euria\EuriaFacade as Euria;

class StructuredAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'JSON assistant.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['answer' => ['type' => 'string']],
            'required' => ['answer'],
        ];
    }
}

it('can use structured output', function () {
    $fake = Euria::fake();
    $fake->fakeText(json_encode(['answer' => 'OK']));

    $response = (new StructuredAgent)->prompt('Test');
    expect((string) $response)->toBe(json_encode(['answer' => 'OK']));
});
