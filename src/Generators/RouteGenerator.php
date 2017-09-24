<?php

namespace LaraSpells\Generator\Generators;

use Closure;

class RouteGenerator extends BaseGenerator
{

    protected $method = '';

    protected $path = '';

    protected $uses = '';

    protected $group;

    protected $wheres = [];

    protected $options = [];

    public function __construct($method, $path, $uses, array $options = [], RouteGroupGenerator $group = null)
    {
        $this->method = $method;
        $this->path = $path;
        $this->uses = $uses;
        $this->group = $group;
        $this->options = $options;
    }

    /**
     * Get route group
     *
     * @return RouteGroupGenerator
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Set Route Middleware
     *
     * @param string $middleware
     * @return void
     */
    public function setMiddleware($middleware)
    {
        $this->setOption('middleware', $middleware);
        return $this;
    }

    /**
     * Get Route Middleware
     *
     * @return string
     */
    public function getMiddleware()
    {
        return $this->getOption('middleware');
    }

    /**
     * Set Route Name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->setOption('name', $name);
        return $this;
    }

    /**
     * Get Route Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->getOption('name');
    }

    /**
     * Get route full name
     *
     * @return string
     */
    public function getFullName()
    {
        $group = $this->getGroup();
        return $group ? $group->getFullName() . $this->getName() : $this->getName();
    }

    /**
     * Set Route Path
     *
     * @param string $path
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get Route Path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set Route Uses
     *
     * @param string $uses
     * @return void
     */
    public function setUses($uses)
    {
        $this->uses = $uses;
        return $this;
    }

    /**
     * Get Route Uses
     *
     * @return string
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * Set Route Method
     *
     * @param string $method
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Get Route Method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Add route where
     *
     * @param string $key
     * @return void
     */
    public function where($key, $regex)
    {
        $this->wheres[$key] = $regex;
    }

    /**
     * Get route wheres
     *
     * @return array
     */
    public function getWheres()
    {
        return $this->wheres;
    }

    /**
     * Get Route Option
     *
     * @param string $key
     * @return mixed
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    /**
     * Set Route Option
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function generateLines()
    {
        $method = camel_case(strtolower($this->getMethod()));
        $path = $this->getPath();
        $uses = $this->getUses();
        $name = $this->getName();
        $wheres = $this->getWheres();
        $middleware = $this->getMiddleware();

        $chains = [];
        if ($name) $chains[] = "name('{$name}')";
        if ($middleware) $chains[] = "middleware('{$middleware}')";
        foreach($wheres as $key => $regex) {
            $chains[] = "where('{$key}', '{$regex}')";
        }
        $chains = implode("->", $chains);
        if ($chains) {
            $chains = "->" . $chains;
        }

        return $this->applyIndents([
            "Route::{$method}('{$path}', '{$uses}'){$chains};"
        ], $this->getCountIndent());
    }

    protected function getCountIndent()
    {
        $countIndent = 0;
        $group = $this->getGroup();
        while ($group) {
            $countIndent++;
            $group = $group->getGroup();
        }
        return $countIndent;
    }

}
