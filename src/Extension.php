<?php

namespace LaraSpells\Generator;

use Exception;
use LaraSpells\Generator\Commands\SchemaBasedCommand;
use LaraSpells\Generator\Exceptions\InvalidSchemaException;
use LaraSpells\Generator\Schema\Table;

abstract class Extension
{

    protected $command;

    public function __construct(SchemaBasedCommand $command)
    {
        $this->command = $command;
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function register()
    {
        // Do nothing
    }

    public function beforeGenerateCruds(array $tables)
    {
        // Do nothing
    }

    public function beforeGenerateEachCrud(Table $table)
    {
        // Do nothing
    }

    public function afterGenerateEachCrud(Table $table)
    {
        // Do nothing
    }

    public function afterGenerateCruds(array $tables)
    {
        // Do nothing
    }

    public function beforeReports()
    {
        // Do nothing
    }

    public function onEnd()
    {
        // Do nothing
    }

    public function putView($filePath, $content)
    {
        $filePath = $this->getSchema()->getViewPath($filePath);
        return $this->generateFile($filePath, $content);
    }

    public function putController($filePath, $content)
    {
        $filePath = $this->getSchema()->getControllerPath($filePath);
        return $this->generateFile($filePath, $content);
    }

    public function putMigration($filePath, $content)
    {
        if (!preg_match("/^\d{4}_\d{2}_\d{2}_\d{6}_/", $filePath)) {
            $filePath = date('Y_m_d_His').'_'.$filePath;
            if (!ends_with($filePath, '.php')) {
                $filePath .= '.php';
            }
        }

        return $this->generateFile($filePath, $content);
    }

    public function putModel($filePath, $content)
    {
        $filePath = $this->getSchema()->getModelPath($filePath);
        return $this->generateFile($filePath, $content);
    }

    public function resolveModelClassName($class)
    {
        return $this->getSchema()->getModelClass($class);
    }

    public function resolveControllerClassName($class)
    {
        return $this->getSchema()->getControllerClass($class);
    }

    public function renderStub($stub, array $params = [])
    {
        $stub = new Stub($stub, $params);
        return $stub->render();
    }

    protected function assertSchemaHas($key, $message = null)
    {
        $key = (array) $key;
        if (is_null($message)) {
            $class = get_class($this);
            $message = "Extension '{$class}' requires key '{{key}}' in schema.";
        }
        foreach($key as $k) {
            if (!$this->getSchema()->has($k)) {
                throw new InvalidSchemaException(str_replace('{{key}}', $k, $message));
            }
        }
    }

    public function __call($method, array $args)
    {
        $command = $this->getCommand();
        if (is_callable([$command, $method])) {
            return call_user_func_array([$command, $method], $args);
        } else {
            throw new Exception("Call to undefined method '{$method}'.");
        }
    }

}
