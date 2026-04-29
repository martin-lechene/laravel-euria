<?php

namespace MartinLechene\Euria\Console;

use Illuminate\Console\Command;
use MartinLechene\Euria\EuriaFacade as Euria;

class TestConnectionCommand extends Command
{
    protected $signature = 'euria:test';

    protected $description = 'Tester la connexion à l\'API Infomaniak Euria';

    public function handle(): int
    {
        $this->info('Test de connexion Infomaniak AI Services...');

        try {
            $response = Euria::text('Réponds "OK" en un seul mot.');
            $this->info('✅ Connexion réussie !');
            $this->line('Réponse : ' . (string) $response);
            $this->line('Modèle  : ' . $response->model);
            $this->line('Tokens  : ' . $response->usage['total_tokens']);
        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());

            return 1;
        }

        return 0;
    }
}
