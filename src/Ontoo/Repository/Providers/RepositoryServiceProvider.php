<?php

namespace Ontoo\Repository\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class RepositoryServiceProvider
 *
 * @package Ontoo\Repository\Providers
 */
class RepositoryServiceProvider extends ServiceProvider
{

    /**
     * Boot the package.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../resources/config/repository.php' => config_path('repository.php')
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../../resources/config/repository.php', 'repository'
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            \Ontoo\Commands\RepositoryCommand::class,
            \Ontoo\Commands\TransformerCommand::class,
            \Ontoo\Commands\PresenterCommand::class,
        ]);
    }
}
