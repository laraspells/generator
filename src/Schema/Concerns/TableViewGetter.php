<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableViewGetter
{

    public function getViewDirectory()
    {
        return $this->get('view.directory') ?: str_singular($this->getName());
    }

    public function getViewNamespace()
    {
        return $this->get('view.namespace');
    }

    public function getViewPath($view = null)
    {
        $dir = $this->getViewDirectory();
        $viewPath = $this->get('view.path');
        $baseViewPath = "{$viewPath}/{$dir}";
        return $view? "{$baseViewPath}/{$view}.blade.php" : $baseViewPath;
    }

    public function getViewName($view)
    {
        $dir = $this->getViewDirectory();
        $view = "{$dir}.{$view}";
        $namespace = $this->getViewNamespace();
        return $namespace? $namespace.'::'.$view : $view;
    }

    public function getViewListPath()
    {
        return $this->getViewPath('page-list');
    }

    public function getViewDetailPath()
    {
        return $this->getViewPath('page-detail');
    }

    public function getViewCreatePath()
    {
        return $this->getViewPath('form-create');
    }

    public function getViewEditPath()
    {
        return $this->getViewPath('form-edit');
    }

    public function getViewListName()
    {
        return $this->getViewName('page-list');
    }

    public function getViewDetailName()
    {
        return $this->getViewName('page-detail');
    }

    public function getViewCreateName()
    {
        return $this->getViewName('form-create');
    }

    public function getViewEditName()
    {
        return $this->getViewName('form-edit');
    }

}
