<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableRouteGetter
{
    public function getRouteFile()
    {
        return $this->get('route.file');
    }

    public function getRoutePrefix()
    {
        return trim($this->get('route.prefix'), '/');
    }

    public function getRoutePath()
    {
        return trim($this->get('route.path') ?: str_replace("_", "-", $this->getSingularName()), '/');
    }

    public function getRouteDomain()
    {
        return $this->get('route.domain');
    }

    public function getRouteMiddleware()
    {
        return $this->get('route.middleware');
    }

    public function getRouteFullPath()
    {
        $path = $this->getRoutePath();
        $prefix = $this->getRoutePrefix();
        return "{$prefix}/{$path}";
    }

    public function getRouteNamespace($resolve = true)
    {
        $namespace = $this->has('route.namespace') ? $this->get('route.namespace') : $this->getControllerNamespace();

        if ($resolve) {
            $baseNamespace = $this->getRouteBaseNamespace();
            if (starts_with($namespace, $baseNamespace)) {
                return trim(str_replace($baseNamespace, '', $namespace), '\\');
            } else {
                return trim($namespace, '\\');
            }
        } else {
            return trim($namespace, '\\');
        }
    }

    public function getRouteBaseNamespace()
    {
        return $this->get('route.base_namespace');
    }

    public function getRouteName($action = '', $includeNamespace = true)
    {
        $path = $this->getRoutePath();
        $namespace = $this->get('route.name');
        $route = "{$path}.{$action}";
        return $includeNamespace? $namespace.$route : $route;
    }

    public function getRouteIndexName($includeNamespace = true)
    {
        return $this->getRouteName('index', $includeNamespace);
    }

    public function getRouteShowName($includeNamespace = true)
    {
        return $this->getRouteName('show', $includeNamespace);
    }

    public function getRouteCreateName($includeNamespace = true)
    {
        return $this->getRouteName('create', $includeNamespace);
    }

    public function getRouteStoreName($includeNamespace = true)
    {
        return $this->getRouteName('store', $includeNamespace);
    }

    public function getRouteEditName($includeNamespace = true)
    {
        return $this->getRouteName('edit', $includeNamespace);
    }

    public function getRouteUpdateName($includeNamespace = true)
    {
        return $this->getRouteName('update', $includeNamespace);
    }

    public function getRouteDestroyName($includeNamespace = true)
    {
        return $this->getRouteName('destroy', $includeNamespace);
    }

}
