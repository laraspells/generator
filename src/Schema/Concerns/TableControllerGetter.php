<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableControllerGetter
{

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
    public function getControllerClass($namespace = true)
    {
        $table = $this->getName();
        $controller = $this->get('controller.class') ?: ucfirst(camel_case($this->getSingularName())).'Controller';
        return $namespace? $this->getRootSchema()->getControllerClass($controller) : $controller;
    }

}
