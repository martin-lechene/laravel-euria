<?php

namespace MartinLechene\Euria\Contracts;

interface Conversational
{
    public function messages(): iterable;
}
