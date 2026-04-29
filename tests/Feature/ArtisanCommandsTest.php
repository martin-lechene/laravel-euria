<?php

it('euria:models affiche les modèles', function () {
    $this->artisan('euria:models')
        ->expectsOutputToContain('LLM Text')
        ->assertExitCode(0);
});
