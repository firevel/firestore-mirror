<?php

namespace Firevel\FirestoreMirror;

use Firevel\FirestoreMirror\Console\FlushCommand;
use Firevel\FirestoreMirror\Console\ImportCommand;
use Illuminate\Support\ServiceProvider;

class FirestoreMirrorServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/firestore-mirror.php', 'firestore-mirror');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                FlushCommand::class,
                ImportCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/firestore-mirror.php' => $this->app['path.config'].DIRECTORY_SEPARATOR.'firestore-mirror.php',
            ]);
        }
    }
}
