<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableControllerGetter
{

    /**
     * Get controller namespace
     *
     * @return string
     */
    public function getControllerNamespace()
    {
        return $this->get('controller.namespace');
    }

    /**
     * Get controller filepath
     *
     * @return string
     */
    public function getControllerPath()
    {
        $controller = $this->getControllerClass(false);
        $controllerPath = $this->get('controller.path');
        return $controllerPath.'/'.$controller.'.php';
    }

    /**
     * Get controller class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getControllerClass($includeNamespace = true)
    {
        $table = $this->getName();
        $namespace = $this->getControllerNamespace();
        $controller = $this->get('controller.class') ?: ucfirst(camel_case($this->getSingularName())).'Controller';
        return $includeNamespace? $namespace.'\\'.$controller : $controller;
    }

}
