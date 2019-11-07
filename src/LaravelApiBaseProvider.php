<?php

namespace LaravelApiBase;

use App\Services\ApiRouter;
use Illuminate\Support\ServiceProvider;

class LaravelApiBaseProvider extends ServiceProvider
{

    public function boot()
    { 

    }

    public function register()
    { 
        $this->registerRouter();
    }

    /**
	 * Register the router instance.
	 *
	 * @return void
	 */
	protected function registerRouter()
	{
		$this->app['router'] = $this->app->share(function ($app) {
			return new ApiRouter($app['events'], $app);
		});
    }
    
    /**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'router',
		];
	}

}
