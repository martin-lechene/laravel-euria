<?php

namespace MartinLechene\Euria\Agents;

use Illuminate\Foundation\Bus\PendingDispatch;
use MartinLechene\Euria\Contracts\Agent;
use MartinLechene\Euria\Contracts\Conversational;
use MartinLechene\Euria\Contracts\HasStructuredOutput;
use MartinLechene\Euria\Contracts\HasTools;
use MartinLechene\Euria\EuriaFacade as Euria;

class AgentRunner
{
    public function run(
        Agent $agent,
        string $message,
        ?string $model = null,
        int $timeout = 60,
        array $attachments = [],
    ): mixed {
        $instructions = (string) $agent->instructions();

        $messages = $this->buildMessages($agent, $instructions, $message);

        $options = [];
        if ($agent instanceof HasTools) {
            /** @var array<int, mixed> $tools */
            $tools = $agent->tools();
            $options['tools'] = $this->resolveTools($tools);
        }

        $schema = null;
        if ($agent instanceof HasStructuredOutput) {
            $schema = $agent->schema();
        }

        $response = Euria::model($model ?? '')->timeout($timeout)->text(
            $message,
            array_merge($options, $schema ? ['schema' => $schema] : [])
        );

        return $response;
    }

    public function stream(
        Agent $agent,
        string $message,
        ?string $model = null,
    ): \Generator {
        $instructions = (string) $agent->instructions();

        $messages = [
            ['role' => 'system', 'content' => $instructions],
            ['role' => 'user', 'content' => $message],
        ];

        yield from Euria::model($model ?? '')->stream($message, ['messages' => $messages]);
    }

    public function queue(Agent $agent, string $message): PendingDispatch
    {
        return dispatch(function () use ($agent, $message) {
            if (method_exists($agent, 'prompt')) {
                $agent->prompt($message);
            }
        });
    }

    protected function buildMessages(Agent $agent, string $instructions, string $message): array
    {
        $messages = [
            ['role' => 'system', 'content' => $instructions],
            ['role' => 'user', 'content' => $message],
        ];

        if ($agent instanceof Conversational) {
            foreach ($agent->messages() as $msg) {
                $messages[] = $msg;
            }
        }

        return $messages;
    }

    protected function resolveTools(array $tools): array
    {
        $resolved = [];
        foreach ($tools as $tool) {
            if (is_object($tool) && method_exists($tool, 'toArray')) {
                $resolved[] = $tool->toArray();
            }
        }

        return $resolved;
    }
}
