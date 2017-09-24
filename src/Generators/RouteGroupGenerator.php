<?php

namespace LaraSpells\Generator\Generators;

use Closure;

class RouteGroupGenerator extends RouteGenerator
{

    protected $routes = [];

    public function __construct(array $options = [], RouteGroupGenerator $group = null)
    {
        $this->options = $options;
        $this->group = $group;
    }

    /**
     * Set Route Namespace
     *
     * @param string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->setOption('namespace', $namespace);
        return $this;
    }

    /**
     * Get Route Prefix
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->getOption('namespace');
    }

    /**
     * Set Route Namespace
     *
     * @param string $domain
     * @return void
     */
    public function setDomain($domain)
    {
        $this->setOption('domain', $domain);
        return $this;
    }

    /**
     * Get Route Prefix
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->getOption('domain');
    }

    /**
     * Get Route Prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->getOption('prefix');
    }

    /**
     * Set Route Prefix
     *
     * @param string $domain
     * @return void
     */
    public function setPrefix($prefix)
    {
        return $this->setOption('prefix', $prefix);
    }

    public function addRouteGroup(array $options = [])
    {
        return $this->register(new static($options, $this));
    }

    public function addRoute($method, $path, $uses, array $options = [])
    {
        return $this->register(new RouteGenerator($method, $path, $uses, $options, $this));
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

    public function generateLines()
    {
        $routes = $this->getRoutes();
        if (!$routes) {
            return [];
        }

        $method = 'group';
        $name = $this->getName();
        $domain = $this->getDomain();
        $prefix = $this->getPrefix();
        $namespace = $this->getNamespace();
        $middleware = $this->getMiddleware();

        $chains = [];
        if ($domain) $chains[] = "domain('{$domain}')";
        if ($name) $chains[] = "name('{$name}')";
        if ($prefix) $chains[] = "prefix('{$prefix}')";
        if ($middleware) $chains[] = "middleware('{$middleware}')";
        if ($namespace) $chains[] = "namespace('{$namespace}')";
        $chains = implode("->", $chains);

        $childRoutes = [];
        foreach ($routes as $route) {
            $childRoutes[] = $route->generateCode();
        }
        $childRoutes = implode("\n", $childRoutes);

        $lines = $this->parseLines("
            Route::{$chains}->group(function() {
                {$childRoutes}
            });
        ");

        return $this->applyIndents($lines, $this->getCountIndent());
    }

    public function findRouteNamed($name)
    {
        foreach ($this->routes as $route) {
            $routeName = $route->getFullName();
            if ($routeName == $name) {
                return $route;
            }
            if ($route instanceof RouteGroupGenerator) {
                $found = $route->findRouteNamed($name);
                if ($found) return $found;
            }
        }
    }

}
