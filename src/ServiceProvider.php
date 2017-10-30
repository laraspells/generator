<?php

namespace LaraSpells\Generator;

use Blade;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use LaraSpells\Generator\Commands\GenerateCommand;
use LaraSpells\Generator\Commands\MakeSchemaCommand;
use LaraSpells\Generator\Commands\ShowCommand;

class ServiceProvider extends BaseServiceProvider
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
                ShowCommand::class,
                MakeSchemaCommand::class,
            ]);
        }
    }

}
