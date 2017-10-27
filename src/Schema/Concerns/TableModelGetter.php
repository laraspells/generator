<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableModelGetter
{

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
    public function getModelClass($namespace = true)
    {
        $table = $this->getName();
        $model = $this->get('model.class') ?: ucfirst(camel_case($this->getSingularName()));
        return $namespace? $this->getRootSchema()->getModelClass($model) : $model;
    }

}
