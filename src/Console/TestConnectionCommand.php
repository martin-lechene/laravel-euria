<?php

namespace MartinLechene\Euria\Console;

use Exception;
use Illuminate\Console\Command;
use MartinLechene\Euria\Facades\Euria;

class TestConnectionCommand extends Command
{
    protected $signature = 'euria:test';

    protected $description = 'Test connection to the Infomaniak Euria API';

    public function handle(): int
    {
        $this->info('Testing Infomaniak AI Services connection...');

        try {
            $response = Euria::text('Respond with "OK" in a single word.');
            $this->info('✅ Connection successful!');
            $this->line('Response: ' . (string) $response);
            $this->line('Model   : ' . $response->model);
            $this->line('Tokens  : ' . $response->usage['total_tokens']);
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }
}
