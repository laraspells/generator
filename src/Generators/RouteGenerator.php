<?php

namespace LaraSpells\Generators;

use Closure;

class RouteGenerator extends BaseGenerator
{

    const TYPE_ROUTE = 'route';
    const TYPE_GROUP = 'group';

    protected $level;

    protected $parent;

    protected $routes = [];

    protected $middleware = '';

    protected $namespace = '';

    protected $prefix = '';

    protected $name = '';

    protected $domain;

    public function __construct($level = 0)
    {
        $this->level = $level;
    }

    /**
     * Set route parent
     *
     * @param  LaraSpells\Generators\RouteGenerator $parent
     * @return void
     */
    public function setParent(RouteGenerator $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Add new route.
     *
     * @param  string $method
     * @param  string $path
     * @param  string $uses
     * @param  array $options
     * @return void
     */
    public function addRoute($method, $path, $uses, array $options = [])
    {
        if (isset($options['name'])) {
            $options['name'] = $this->combineName($this->getName(), $options['name']);
        }

        if (isset($options['middleware'])) {
            $options['middleware'] = $this->combineMiddleware($this->getMiddleware(), $options['middleware']);
        }

        $this->register(self::TYPE_ROUTE, [
            'method' => $method,
            'path' => $this->combinePath($this->getPrefix(), $path),
            'uses' => $this->combineNamespace($this->getNamespace(), $uses),
            'options' => array_merge([
                'middleware' => null,
                'name' => null
            ], $options)
        ]);
    }

    /**
     * Add new group
     *
     * @param  array $options
     * @param  Closure $callback
     * @return RouteGenerator child group
     */
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
        $routes->setParent($this);
        $routes->setMiddleware($options['middleware']);
        $routes->setPrefix($options['prefix']);
        $routes->setDomain($options['domain']);
        $routes->setName($options['name']);
        $routes->setNamespace($options['namespace']);
        $callback($routes);

        $this->register(self::TYPE_GROUP, [
            'options' => $options,
            'routes' => $routes
        ]);

        return $routes;
    }

    /**
     * Check this generator is root generator.
     *
     * @return bool
     */
    public function isRoot()
    {
        return false === (bool) $this->getParent();
    }

    /**
     * Get parent route generator.
     *
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get registered routes (and groups)
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get all routes including route in groups
     *
     * @return array
     */
    public function getAllRoutes()
    {
        $routes = [];
        foreach($this->getRoutes() as $route) {
            if (self::TYPE_GROUP == $route['type']) {
                $routes = array_merge($routes, $route['routes']->getAllRoutes());
            } else {
                $routes[] = $route;
            }
        }
        return $routes;
    }

    /**
     * Get count routes including route in groups
     *
     * @return int
     */
    public function getCountRoutes()
    {
        return count($this->getAllRoutes());
    }

    /**
     * Check route by name
     *
     * @return bool
     */
    public function hasRouteNamed($name)
    {
        foreach($this->getAllRoutes() as $route) {
            $routeName = array_get($route, 'options.name');
            if ($routeName == $name) return true;
        }
        return false;
    }

    /**
     * Get level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Get middleware.
     *
     * @return string
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * Get controller namespace
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get route name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get path prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Get domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set Middleware.
     *
     * @param  string $middleware
     * @return void
     */
    public function setMiddleware($middleware)
    {
        $parent = $this->getParent();
        if ($parent AND !$parent->isRoot()) {
            $parentMiddleware = $parent->getMiddleware();
            $middleware = $this->combineMiddlewares($middleware, $parentMiddleware);
        }
        $this->middleware = $middleware;
    }

    /**
     * Set Namespace.
     *
     * @param  string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $namespace = trim($namespace, "\\");
        $parent = $this->getParent();
        if ($parent AND !$parent->isRoot() AND $parentNamespace = $parent->getNamespace()) {
            $namespace = $this->combineNamespace($parentNamespace, $namespace);
        }
        $this->namespace = $namespace;
    }

    /**
     * Set Name.
     *
     * @param  string $namespace
     * @return void
     */
    public function setName($name)
    {
        $parent = $this->getParent();
        if ($parent AND !$parent->isRoot() AND $parentName = $parent->getName()) {
            $name = $this->combineName($parentName, $name);
        }
        $this->name = $name;
    }

    /**
     * Set Prefix.
     *
     * @param  string $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $parent = $this->getParent();
        if ($parent AND !$parent->isRoot() AND $parentPrefix = $parent->getPrefix()) {
            $prefix = $this->combinePath($parentPrefix, $prefix);
        }
        $this->prefix = $prefix;
    }

    /**
     * Set Domain.
     *
     * @param  string $domain
     * @return void
     */
    public function setDomain($domain)
    {
        $parent = $this->getParent();
        if ($parent AND !$parent->isRoot() AND $parent->getDomain() == $domain) {
            return;
        }
        $this->domain = $domain;
    }

    /**
     * generateLines
     *
     * @return void
     */
    public function generateLines()
    {
        $routes = $this->getRoutes();
        $count = count($routes);
        $code = new CodeGenerator;
        foreach($routes as $i => $route) {
            $code->nl();
            switch($route['type']) {
                case self::TYPE_GROUP: $this->writeGroup($route, $code); break;
                case self::TYPE_ROUTE: $this->writeRoute($route, $code); break;
            }
            if ($i == $count - 1) {
                $code->nl();
            }
        }
        return $this->applyIndents($code->generateLines(), $this->getLevel());
    }

    protected function writeRoute($route, CodeGenerator $code)
    {
        $options = $route['options'];
        $method = strtolower($route['method']);
        $uses = $this->dissolveNamespace($this->getNamespace(), $route['uses']);
        $path = $this->dissolvePath($this->getPrefix(), $route['path']);
        $chain = [];

        if ($options['middleware']) {
            $middleware = $this->dissolveMiddleware($this->getMiddleware(), $options['middleware']);
            $chain[] = "middleware('{$middleware}')";
        }

        $chain[] = "{$method}('{$path}', '{$uses}')";

        if ($options['name']) {
            $name = $this->dissolveName($this->getName(), $options['name']);
            $chain[] = "name('{$name}')";
        }

        $code->addCode("Route::".implode("->", $chain).";");
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

        $code->addCode("Route::".implode("->", $chain).";");
    }

    protected function register($type, array $params = [])
    {
        $this->routes[] = array_merge($params, [
            'type' => $type
        ]);
    }

    protected function combineMiddlewares($a, $b)
    {
        $a = explode("|", $a);
        $b = explode("|", $b);
        return implode("|", array_unique(array_merge($a, $b)));
    }

    protected function combineNamespace($a, $b)
    {
        return trim($a, "\\")."\\".trim($b, "\\");
    }

    protected function combineName($a, $b)
    {
        return $a.$b;
    }

    protected function combinePath($a, $b)
    {
        return trim($a, "/")."/".trim($b, "/");
    }

    protected function dissolveNamespace($a, $b)
    {
        return trim(substr($b, strlen($a)), "\\");
    }

    protected function dissolvePath($a, $b)
    {
        return trim(substr($b, strlen($a)), "/");
    }

    protected function dissolveMiddleware($a, $b)
    {
        $a = explode("|", $a);
        $b = explode("|", $b);
        $diffs = array_diff($b, $a);
        return implode("|", $diffs);
    }

    protected function dissolveName($a, $b)
    {
        return substr($b, strlen($a));
    }


}
