<?php

namespace LaraSpell\Generators;

use Closure;

class RouteGenerator extends BaseGenerator
{

    const TYPE_ROUTE = 'route';
    const TYPE_GROUP = 'group';

    protected $level;

    protected $routes = [];

    protected $groupCursor;

    public function __construct($level = 0)
    {
        $this->level = $level;
    }

    public function addRoute($method, $path, $uses, array $options = [])
    {
        $this->register(static::TYPE_ROUTE, [
            'method' => $method,
            'path' => $path,
            'uses' => $uses,
            'options' => array_merge([
                'middleware' => null,
                'name' => null
            ], $options)
        ]);
    }

    public function addGroup(array $options = [], Closure $callback)
    {
        $options = array_merge([
            'middleware' => null,
            'prefix' => null,
            'domain' => null,
            'namespace' => null,
            'name' => null,
        ], $options);

        $routes = new static($this->level + 1);
        $callback($routes);

        $this->register(static::TYPE_GROUP, [
            'options' => $options,
            'routes' => $routes
        ]);
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function generateLines()
    {
        $routes = $this->getRoutes();
        $count = count($routes);
        $code = new CodeGenerator;
        foreach($routes as $i => $route) {
            $code->ln();
            switch($route['type']) {
                case static::TYPE_GROUP: $this->writeGroup($route, $code); break;
                case static::TYPE_ROUTE: $this->writeRoute($route, $code); break;
            }
            if ($i == $count - 1) {
                $code->ln();
            }
        }
        return $this->applyIndents($code->generateLines(), $this->getLevel());
    }

    protected function writeRoute($route, CodeGenerator $code)
    {
        $options = $route['options'];
        $method = strtolower($route['method']);
        $uses = $route['uses'];
        $path = $route['path'];
        $chain = [];

        if ($options['middleware']) {
            $chain[] = "middleware('{$options['middleware']}')";
        }

        $chain[] = "{$method}('{$path}', '{$uses}')";
        
        if ($options['name']) {
            $chain[] = "name('{$options['name']}')";
        }

        $code->addStatements("Route::".implode("->", $chain).";");
    }

    protected function writeGroup($route, CodeGenerator $code)
    {
        $options = $route['options'];
        $chain = [];
        if ($options['name']) $chain[] = "name('{$options['name']}')";
        if ($options['domain']) $chain[] = "domain('{$options['domain']}')";
        if ($options['prefix']) $chain[] = "prefix('{$options['prefix']}')";
        if ($options['namespace']) $chain[] = "namespace('{$options['namespace']}')";
        if ($options['middleware']) $chain[] = "middleware('{$options['middleware']}')";

        $routes = $route['routes']->generateCode();
        $chain[] = "group(function() {
            {$routes}
        })";

        $code->addStatements("Route::".implode("->", $chain).";");
    }

    protected function register($type, array $params = [])
    {
        $this->routes[] = array_merge($params, [
            'type' => $type
        ]);
    }

}
