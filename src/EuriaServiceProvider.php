<?php

namespace MartinLechene\Euria;

use Illuminate\Support\ServiceProvider;
use MartinLechene\Euria\Console\ListModelsCommand;
use MartinLechene\Euria\Console\MakeAgentCommand;
use MartinLechene\Euria\Console\TestConnectionCommand;

class EuriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/euria.php', 'euria');

        $this->app->singleton(EuriaManager::class, function ($app) {
            return new EuriaManager($app);
        });

        $this->app->alias(EuriaManager::class, 'euria');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/euria.php' => config_path('euria.php'),
            ], 'euria-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'euria-migrations');

            $this->publishes([
                __DIR__ . '/../stubs/' => base_path('stubs/euria'),
            ], 'euria-stubs');

            $this->commands([
                MakeAgentCommand::class,
                ListModelsCommand::class,
                TestConnectionCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
