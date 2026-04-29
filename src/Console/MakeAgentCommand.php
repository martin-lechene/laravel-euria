<?php

namespace MartinLechene\Euria\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeAgentCommand extends GeneratorCommand
{
    protected $name = 'make:euria-agent';

    protected $description = 'Create a new Euria Agent';

    protected $type = 'Agent';

    protected function getStub(): string
    {
        return $this->option('structured')
            ? __DIR__ . '/../../stubs/agent.structured.stub'
            : __DIR__ . '/../../stubs/agent.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Ai\Agents';
    }

    protected function getOptions(): array
    {
        return [
            ['structured', 's', InputOption::VALUE_NONE, 'Créer un agent avec structured output'],
        ];
    }
}
