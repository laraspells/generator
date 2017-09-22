<?php

namespace LaraSpells\Generator\Schema;

use LaraSpells\Generator\SchemaResolverInterface;

class Schema extends AbstractSchema
{

    protected $tables = [];

    public function __construct($schema)
    {
        parent::__construct($schema);
        $this->initTables($schema);
    }

    /**
     * Get extensions classes
     *
     * @return string
     */
    public function getExtensions()
    {
        return (array) $this->get('extensions');
    }

    /**
     * Get template directory
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->get('template');
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Get author name
     *
     * @return string
     */
    public function getAuthorName()
    {
        return $this->get('author.name');
    }

    /**
     * Get author email
     *
     * @return string
     */
    public function getAuthorEmail()
    {
        return $this->get('author.email');
    }

    /**
     * Get table schemas
     *
     * @return array of LaraSpells\Generator\Schema\Table
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Get table schema by table name
     *
     * @return LaraSpells\Generator\Schema\Table
     */
    public function getTable($tableName)
    {
        return isset($this->tables[$tableName])? $this->tables[$tableName] : null;
    }

    /**
     * Add new table schema
     */
    public function addTable($table, Table $tableSchema)
    {
        $tableSchema->setRootSchema($this);
        $this->tables[$table] = $tableSchema;
    }

    /**
     * Get controller filepath
     *
     * @param string $controller
     * @return string
     */
    public function getControllerPath($controller = null)
    {
        $controllerPath = $this->get('controller.path');
        return $controller? $controllerPath.'/'.$controller.'.php' : $controllerPath;
    }

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
     * Get controller class name (with namespace)
     *
     * @param string $controller
     * @return string
     */
    public function getControllerClass($controller)
    {
        return $this->getControllerNamespace()."\\".$controller;
    }

    /**
     * Get request filepath
     *
     * @param string $request
     * @return string
     */
    public function getRequestPath($request = null)
    {
        $requestPath = $this->get('request.path');
        return $request? $requestPath.'/'.$request.'.php' : $requestPath;
    }

    /**
     * Get request namespace
     *
     * @return string
     */
    public function getRequestNamespace()
    {
        return $this->get('request.namespace');
    }

    /**
     * Get request class name (with namespace)
     *
     * @param string $request
     * @return string
     */
    public function getRequestClass($request)
    {
        return $this->getRequestNamespace()."\\".$request;
    }

    /**
     * Get model filepath
     *
     * @param string $model
     * @return string
     */
    public function getModelPath($model = null)
    {
        $modelPath = $this->get('model.path');
        return $model? $modelPath.'/'.$model.'.php' : $modelPath;
    }

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
     * Get service provider filepath
     *
     * @return string
     */
    public function getServiceProviderPath()
    {
        return $this->fillExtension($this->get('provider.file'), 'php');
    }

    /**
     * Get service provider class
     *
     * @return string
     */
    public function getServiceProviderClass()
    {
        return $this->get('provider.class');
    }

    /**
     * Get model class name (with namespace)
     *
     * @param string $Model
     * @return string
     */
    public function getModelClass($model)
    {
        return $this->getModelNamespace()."\\".$model;
    }

    /**
     * Get view filepath
     *
     * @param string $view
     * @return string
     */
    public function getViewPath($view = null)
    {
        $viewPath = $this->get('view.path');
        return $view? $viewPath.'/'.$view.'.blade.php' : $viewPath;
    }

    /**
     * Get view namespace
     *
     * @return string
     */
    public function getViewNamespace()
    {
        return $this->get('view.namespace');
    }

    /**
     * Get view declaration (with namespace)
     *
     * @param string $view
     * @return string
     */
    public function getView($view)
    {
        $namespace = $this->getViewNamespace();
        return $namespace? $namespace.'::'.$view : $view;
    }

    /**
     * Get migration file path
     *
     * @return string
     */
    public function getMigrationPath($table = null)
    {
        $path = $this->get('migration.path');
        return $table? $path.'/'.date('Y_m_d_His').'_create_'.$table.'_table.php' : $path;
    }

    /**
     * Get migration file path
     *
     * @return string
     */
    public function getMigrationClass($table)
    {
        $className = ucfirst(camel_case($table));
        return 'Create'.$className.'Table';
    }

    /**
     * Get route name
     *
     * @param  string $route
     * @param  bool $includeNamespace
     * @return string
     */
    public function getRouteName($route = '', $includeNamespace = true)
    {
        $namespace = $this->get('route.name');
        return $includeNamespace? $namespace.$route : $route;
    }

    /**
     * Get route controller namespace.
     *
     * @return string
     */
    public function getRouteNamespace()
    {
        return $this->get('route.namespace');
    }

    /**
     * Get route prefix
     *
     * @return string
     */
    public function getRoutePrefix()
    {
        return $this->get('route.prefix');
    }

    /**
     * Get route middleware
     *
     * @return string
     */
    public function getRouteMiddleware()
    {
        return $this->get('route.middleware');
    }

    /**
     * Get route domain
     *
     * @return string
     */
    public function getRouteDomain()
    {
        return $this->get('route.domain');
    }

    /**
     * Get route file
     *
     * @return string
     */
    public function getRouteFile()
    {
        return $this->fillExtension($this->get('route.file'), 'php');
    }

    /**
     * Get config file
     *
     * @return string
     */
    public function getConfigFile()
    {
        return $this->fillExtension($this->get('config_file'), 'php');
    }

    /**
     * Get upload disk
     *
     * @return string
     */
    public function getUploadDisk()
    {
        return $this->get('upload_disk') ?: 'uploads';
    }

    /**
     * Get config key
     *
     * @return string
     */
    public function getConfigKey()
    {
        return str_replace("/", ".", $this->removeExtension($this->getConfigFile()));
    }

    /**
     * Fill extension to string
     *
     * @param string $str
     * @param string $extension
     * @return string
     */
    protected function fillExtension($str, $extension)
    {
        if (!ends_with($str, '.'.$extension)) {
            $str .= '.'.$extension;
        }
        return $str;
    }

    /**
     * Remove extension from given string
     *
     * @param string $str
     * @return string
     */
    protected function removeExtension($str)
    {
        $pathinfo = pathinfo($str);
        return ($pathinfo['dirname'] != '.')? $pathinfo['dirname'].'/'.$pathinfo['filename'] : $pathinfo['filename'];
    }

    /**
     * Initialize table schemas
     *
     * @param array $schema
     * @return void
     */
    protected function initTables(array $schema)
    {
        $this->tables = [];
        $tables = $schema['tables'];
        foreach($tables as $table => $schema) {
            $schema = new Table($table, $schema);
            $this->addTable($table, $schema);
        }
    }
}
