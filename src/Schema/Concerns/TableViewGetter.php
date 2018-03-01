<?php

namespace LaraSpells\Generator\Schema\Concerns;

trait TableViewGetter
{

    public function getViewBaseDirectory()
    {
        return $this->get('view.base_dir') ?: '';
    }

    public function getViewDirectory()
    {
        return $this->get('view.directory') ?: $this->getSingularName();
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
        $baseDir = $this->getViewBaseDirectory();
        $view = $baseDir ? "{$baseDir}.{$dir}.{$view}" : "{$dir}.{$view}";
        $namespace = $this->getViewNamespace();
        return $namespace? $namespace.'::'.$view : $view;
    }

    public function getViewListPath()
    {
        return $this->getViewPath('index');
    }

    public function getViewDetailPath()
    {
        return $this->getViewPath('show');
    }

    public function getViewCreatePath()
    {
        return $this->getViewPath('create');
    }

    public function getViewEditPath()
    {
        return $this->getViewPath('edit');
    }

    public function getViewListName()
    {
        return $this->getViewName('index');
    }

    public function getViewDetailName()
    {
        return $this->getViewName('show');
    }

    public function getViewCreateName()
    {
        return $this->getViewName('create');
    }

    public function getViewEditName()
    {
        return $this->getViewName('edit');
    }

}
