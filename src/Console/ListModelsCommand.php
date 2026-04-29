<?php

namespace MartinLechene\Euria\Console;

use Illuminate\Console\Command;
use MartinLechene\Euria\EuriaManager;

class ListModelsCommand extends Command
{
    protected $signature = 'euria:models';

    protected $description = 'Lister les modèles disponibles sur l\'API Infomaniak';

    public function handle(EuriaManager $euria): int
    {
        $this->info('Modèles Infomaniak AI Services disponibles :');
        $this->table(
            ['Capacité', 'Modèle', 'Défaut config'],
            [
                ['LLM Text',    'mixtral, llama-3, deepseek, mistral-7b', config('euria.defaults.text')],
                ['Embeddings',  'text-embedding-3-small',                 config('euria.defaults.embedding')],
                ['Images',      'sdxl, flux',                             config('euria.defaults.image')],
                ['Audio STT',   'whisper-1',                              config('euria.defaults.audio')],
            ]
        );

        return 0;
    }
}
