<?php

it('euria:models displays the models', function () {
    $this->artisan('euria:models')
        ->expectsOutputToContain('LLM Text')
        ->assertExitCode(0);
});
