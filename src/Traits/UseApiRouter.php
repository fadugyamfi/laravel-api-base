<?php

namespace LaravelApiBase\Traits;

trait UseApiRouter
{
    /**
     * Get the route dispatcher callback.
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        $this->router = $this->app['router'];

        foreach ($this->routeMiddleware as $key => $middleware) {
            $this->router->middleware($key, $middleware);
        }
        
        return parent::dispatchToRouter();
    }
}
