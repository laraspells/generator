<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableModelGetter
{

    /**
     * Get model namespace
     *
     * @return string
     */
    public function getModelNamespace()
    {
        return $this->get('model.namespace');
    }

    /**
     * Get model file path
     *
     * @return string
     */
    public function getModelPath()
    {
        $model = $this->getModelClass(false);
        $modelPath = $this->get('model.path');
        return $modelPath.'/'.$model.'.php';
    }

    /**
     * Get model class name
     *
     * @param boolean $namespace
     * @return string
     */
    public function getModelClass($includeNamespace = true)
    {
        $table = $this->getName();
        $namespace = $this->getModelNamespace();
        $model = $this->get('model.class') ?: ucfirst(camel_case($this->getSingularName()));
        return $includeNamespace? $namespace.'\\'.$model : $model;
    }

}
