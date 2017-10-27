<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableRouteGetter
{

    public function getRouteName($action = '', $includeNamespace = true)
    {
        $prefix = $this->getRoutePrefix();
        $namespace = $this->get('route.name');
        $route = "{$prefix}.{$action}";
        return $includeNamespace? $namespace.$route : $route;
    }

    public function getRoutePrefix()
    {
        return str_replace("_", "-", $this->getName());
    }

    public function getRouteListName($includeNamespace = true)
    {
        return $this->getRouteName('page-list', $includeNamespace);
    }

    public function getRouteDetailName($includeNamespace = true)
    {
        return $this->getRouteName('page-detail', $includeNamespace);
    }

    public function getRouteCreateName($includeNamespace = true)
    {
        return $this->getRouteName('form-create', $includeNamespace);
    }

    public function getRoutePostCreateName($includeNamespace = true)
    {
        return $this->getRouteName('post-create', $includeNamespace);
    }

    public function getRouteEditName($includeNamespace = true)
    {
        return $this->getRouteName('form-edit', $includeNamespace);
    }

    public function getRoutePostEditName($includeNamespace = true)
    {
        return $this->getRouteName('post-edit', $includeNamespace);
    }

    public function getRouteDeleteName($includeNamespace = true)
    {
        return $this->getRouteName('delete', $includeNamespace);
    }

}
