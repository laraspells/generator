<?php

namespace LaraSpells;

use Blade;
use Illuminate\Support\ServiceProvider;
use LaraSpells\Commands\GenerateCommand;
use LaraSpells\Commands\MakeSchemaCommand;

class LaraSpellsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateCommand::class,
                MakeSchemaCommand::class,
            ]);
        }
    }

}
