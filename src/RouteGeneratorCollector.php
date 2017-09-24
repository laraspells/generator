<?php

namespace LaraSpells\Generator;

use LaraSpells\Generator\Generators\RouteGenerator;
use LaraSpells\Generator\Generators\RouteGroupGenerator;

class RouteGeneratorCollector
{

    protected $routes = [];

    public function addRouteGroup(array $options = [])
    {
        return $this->register(new RouteGroupGenerator($options));
    }

    public function addRoute($method, $path, $uses, array $options = [])
    {
        return $this->register(new RouteGenerator($method, $path, $uses, $options));
    }

    protected function register(RouteGenerator $routeGenerator)
    {
        $this->routes[] = $routeGenerator;
        return $routeGenerator;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

}
