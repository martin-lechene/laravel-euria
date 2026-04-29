<?php

namespace MartinLechene\Euria\Contracts;

use Stringable;

interface Agent
{
    public function instructions(): Stringable|string;
}
