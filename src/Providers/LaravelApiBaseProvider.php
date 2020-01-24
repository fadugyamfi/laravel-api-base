<?php

namespace LaravelApiBase\Providers;

use Illuminate\Support\ServiceProvider;
use LaravelApiBase\Services\ApiResourceRegistrar;

class LaravelApiBaseProvider extends ServiceProvider
{

    public function boot()
    { 
        $this->loadRoutesFrom(__DIR__ . './../routes/api.php');
    }

    public function register()
    { 
        $this->registerResourceRegistrar();
    }

    /**
	 * Register the router instance.
	 *
	 * @return void
	 */
	protected function registerResourceRegistrar()
	{
        $this->app->bind('Illuminate\Routing\ResourceRegistrar', function () {
            return new ApiResourceRegistrar($this->app['router']);
        });
    }

}
