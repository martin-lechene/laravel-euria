<?php

namespace MartinLechene\Euria\Contracts;

interface HasStructuredOutput
{
    public function schema(): array;
}
