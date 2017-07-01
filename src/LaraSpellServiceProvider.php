<?php

namespace LaraSpell;

use Blade;
use Illuminate\Support\ServiceProvider;
use LaraSpell\Commands\GenerateCommand;
use LaraSpell\Commands\MakeSchemaCommand;

class LaraSpellServiceProvider extends ServiceProvider
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
