<?php 

namespace LaravelApiBase\Services;

use Illuminate\Routing\Router as BaseRouter;

class ApiRouter extends BaseRouter {
 
    /**
     * Route an API resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array  $options
     * @return \Illuminate\Routing\PendingResourceRegistration
     */
    public function apiResource($name = null, $controller = null, array $options = [])
    {
        $only = ['index', 'show', 'store', 'update', 'destroy', 'count', 'search'];

        if (isset($options['except'])) {
            $only = array_diff($only, (array) $options['except']);
        }

        return $this->resource($name, $controller, array_merge([
            'only' => $only,
        ], $options));
    }
}