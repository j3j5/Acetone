<?php

namespace JDare\Acetone;

use Illuminate\Support\ServiceProvider;

class AcetoneServiceProvider extends ServiceProvider
{

    /**
     * The commands to be registered from the package.
     *
     * @var array
     */
    protected $commands = [
        Commands\CLICommander::class,
    ];

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            realpath(__DIR__.'/../../config/config.php') => config_path('acetone.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Acetone', 'JDare\Acetone\Facades\Acetone');
        });
        $this->app->bind('acetone', function ($app) {
            return new Acetone($app);
        });

        $this->commands($this->commands);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array("acetone");
    }
}
