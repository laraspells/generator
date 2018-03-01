<?php

namespace LaraSpells\Generator\Commands;

use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use LaraSpells\Generator\Exceptions\InvalidSchemaException;
use LaraSpells\Generator\Extension;
use LaraSpells\Generator\SchemaLoader;
use LaraSpells\Generator\SchemaResolver;
use LaraSpells\Generator\Schema\Schema;
use LaraSpells\Generator\Template;
use LaraSpells\Generator\Util;
use Symfony\Component\Yaml\Yaml;

abstract class SchemaBasedCommand extends Command
{

    protected $originalSchema;
    protected $schemaResolver;
    protected $schema;
    protected $template;
    protected $hooks = [];
    protected $schemaFile;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Initialize Schema
     *
     * @param  string $schemaFile
     * @return void
     */
    public function initializeSchema($schemaFile)
    {
        app()->instance(self::class, $this);
        app()->instance(static::class, $this);

        $this->schemaFile = $schemaFile;

        $arraySchema = SchemaLoader::load($this->schemaFile);
        $this->originalSchema = $arraySchema;

        // Initialize Template
        $this->initializeTemplate($arraySchema);

        // Resolve Schema
        $resolver = $this->getTemplate()->getSchemaResolver();
        if (!$resolver) {
            $resolver = new SchemaResolver();
        }

        // Initialize Schema
        $arraySchema = $resolver->resolve($arraySchema);
        $this->schema = new Schema($arraySchema);
        app()->instance(Schema::class, $this->schema);
    }

    /**
     * Get schema file
     *
     * @return string
     */
    public function getSchemaFile()
    {
        return $this->schemaFile;
    }

    /**
     * Get schema instance
     *
     * @return null|LaraSpells\Generator\Schema\Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Initialize template instance from array schema
     *
     * @param  array $arraySchema
     * @return void
     */
    public function initializeTemplate(array $arraySchema)
    {
        if (!isset($arraySchema['template'])) {
            throw new InvalidSchemaException("Schema must have 'template' key.");
        }

        $templateClass = $arraySchema['template'];
        $template = $this->template = app($templateClass);
        if (!$template instanceof Template) {
            throw new InvalidSchemaException("Template '{$templateClass}' must be subclass of '".Template::class."'.");
        }
        $this->validateTemplate($template);
    }

    public function validateTemplate(Template $template)
    {
        $folderStub = $template->getFolderStub();
        $folderView = $template->getFolderView();
        $requiredFiles = [
            $folderStub.'/index.stub',
            $folderStub.'/show.stub',
            $folderStub.'/create.stub',
            $folderStub.'/edit.stub',
            $folderView.'/partials/fields/text.blade.php',
            $folderView.'/partials/fields/number.blade.php',
            $folderView.'/partials/fields/email.blade.php',
            $folderView.'/partials/fields/textarea.blade.php',
            $folderView.'/partials/fields/select.blade.php',
            $folderView.'/partials/fields/select-multiple.blade.php',
            $folderView.'/partials/fields/file.blade.php',
            $folderView.'/partials/fields/checkbox.blade.php',
            $folderView.'/partials/fields/radio.blade.php',
            $folderView.'/layout/master.blade.php',
        ];
        foreach($requiredFiles as $file) {
            if (!$template->hasFile($file)) {
                $filepath = $template->getFilepath($file);
                throw new \Exception("Template must have file '{$filepath}'.");
            }
        }
    }

    /**
     * Get template instance
     *
     * @return null|LaraSpells\Generator\Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Register/set an hook callback
     *
     * @param  string $key
     * @param  callable $callback
     * @return void
     */
    public function hook($key, callable $callback)
    {
        if (!isset($this->hooks[$key])) {
            $this->hooks[$key] = [];
        }
        $this->hooks[$key][] = $callback;
    }

    /**
     * Get hooks by key
     *
     * @param  string $key
     * @return array
     */
    public function getHooks($key)
    {
        return isset($this->hooks[$key])? $this->hooks[$key] : [];
    }

    /**
     * Run hook callback
     *
     * @param  string $key
     * @param  array $params
     * @return void
     */
    public function applyHook($key, array $params = [])
    {
        foreach($this->getHooks($key) as $hook) {
            call_user_func_array($hook, $params);
        }
    }

}
