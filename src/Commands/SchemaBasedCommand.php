<?php

namespace LaraSpell\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use LaraSpell\Exceptions\InvalidSchemaException;
use LaraSpell\SchemaResolver;
use LaraSpell\Schema\Schema;
use LaraSpell\Template;
use Symfony\Component\Yaml\Yaml;
use Closure;

abstract class SchemaBasedCommand extends Command
{
    const TEMPLATE_INIT_FILE = 'init.php';

    protected $originalSchema;
    protected $schemaResolver;
    protected $schema;
    protected $template;
    protected $hooks = [];

    /**
     * Set SchemaResolver instance
     *
     * @param  LaraSpell\SchemaResolver $resolver
     * @return void
     */
    public function setSchemaResolver(SchemaResolver $schemaResolver)
    {
        $this->schemaResolver = $schemaResolver;
    }

    /**
     * Get SchemaResolver instance
     *
     * @return null|LaraSpell\SchemaResolver
     */
    public function getSchemaResolver()
    {
        return $this->schemaResolver;
    }

    /**
     * Initialize Schema
     *
     * @param  string $schemaFile
     * @return void
     */
    protected function initializeSchema($schemaFile)
    {
        if (!ends_with($schemaFile, '.yml')) {
            $schemaFile .= '.yml';
        }

        $schemaFile;
        if (!is_file($schemaFile)) {
            throw new InvalidArgumentException("Schema file '{$schemaFile}' not found.");
        }

        // Parse schema yml file
        $schemaFileContent = file_get_contents($schemaFile);
        $arraySchema = Yaml::parse($schemaFileContent);
        $this->originalSchema = $arraySchema;

        // Initialize Template
        $this->initializeTemplate($arraySchema);

        // Resolve Schema
        $resolver = $this->getSchemaResolver();
        if (!$resolver) {
            $resolver = new SchemaResolver();
        }

        $arraySchema = $resolver->resolve($arraySchema);
        $this->schema = new Schema($arraySchema);
        app()->instance(Schema::class, $this->schema);
    }

    /**
     * Get schema instance
     *
     * @return null|LaraSpell\Schema\Schema
     */
    protected function getSchema()
    {
        return $this->schema;
    }

    /**
     * Initialize template instance from array schema
     *
     * @param  array $arraySchema
     * @return void
     */
    protected function initializeTemplate(array $arraySchema)
    {
        if (!isset($arraySchema['template'])) {
            throw new InvalidSchemaException("Schema must have 'template' key.");
        }

        $templateDir = $arraySchema['template'];
        if (!is_dir($templateDir)) {
            throw new InvalidSchemaException("Template directory '{$templateDir}' not found.");
        }

        $this->template = new Template($templateDir);
        $this->includeTemplateInitFile($this->template);
    }

    /**
     * Include template init file
     *
     * @return void
     */
    protected function includeTemplateInitFile(Template $template)
    {
        $templateInitFile = static::TEMPLATE_INIT_FILE;
        if ($template->hasFile($templateInitFile)) {
            $generator = $this;
            require_once($template->getFilePath($templateInitFile));
        }
    }

    /**
     * Get template instance
     *
     * @return null|LaraSpell\Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Register/set an hook callback
     *
     * @param  string $key
     * @param  Closure $callback
     * @return void
     */
    public function hook($key, Closure $callback)
    {
        $this->hooks[$key] = $callback;
    }

    /**
     * Run hook callback
     *
     * @param  string $key
     * @param  array $params
     * @return void
     */
    protected function applyHook($key, array $params = [])
    {
        if (!isset($this->hooks[$key])) {
            return;
        }

        $hook = $this->hooks[$key];
        call_user_func_array($hook, $params);
    }

}
