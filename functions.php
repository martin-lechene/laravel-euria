<?php

use MartinLechene\Euria\EuriaManager;

if (! function_exists('euria')) {
    function euria(): EuriaManager
    {
        return app(EuriaManager::class);
    }
}
