<?php

namespace LaravelApiBase\Services;

use Illuminate\Routing\ResourceRegistrar as OriginalRegistrar;

class ApiResourceRegistrar extends OriginalRegistrar
{
    // add data to the array
    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = ['count', 'search', 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];


    /**
     * Add the data method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceCount($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/count';

        $action = $this->getResourceAction($name, $controller, 'count', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the search method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Illuminate\Routing\Route
     */
    protected function addResourceSearch($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name) . '/search';

        $action = $this->getResourceAction($name, $controller, 'search', $options);

        return $this->router->get($uri, $action);
    }
}