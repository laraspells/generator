<?php

namespace LaraSpells\Generator\Commands;

use Closure;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use InvalidArgumentException;
use LaraSpells\Generator\Exceptions\InvalidSchemaException;
use LaraSpells\Generator\Extension;
use LaraSpells\Generator\SchemaResolver;
use LaraSpells\Generator\Schema\Schema;
use LaraSpells\Generator\Template;
use LaraSpells\Generator\Util;
use Symfony\Component\Yaml\Yaml;

abstract class SchemaBasedCommand extends Command
{

    const KEY_FILE = '__FILE__';

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

        // Parse schema yml file
        $arraySchema = $this->resolveVariables($this->resolveExtendsSchema($this->loadSchema($schemaFile)));
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
            $folderStub.'/page-list.stub',
            $folderStub.'/page-detail.stub',
            $folderStub.'/form-create.stub',
            $folderStub.'/form-edit.stub',
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

    /**
     * Load schema file
     *
     * @param array $schema
     * @return array
     */
    protected function loadSchema($filepath)
    {
        if (!ends_with($filepath, '.yml')) {
            $filepath .= '.yml';
        }

        if (!is_file($filepath)) {
            throw new Exception("Schema file '{$filepath}' not found.");
        }

        $filepath = realpath($filepath);

        $content = file_get_contents($filepath);
        $arraySchema = Yaml::parse($content);
        $arraySchema[self::KEY_FILE] = $filepath;

        return $this->resolveIncludesSchema($arraySchema, dirname($filepath));
    }

    /**
     * Resolve +include(s)
     *
     * @param array $schema
     * @return array
     */
    protected function resolveIncludesSchema(array $schema, $basedir = '')
    {
        $keyword = '+include';
        foreach ($schema as $key => $value) {
            if ($key === $keyword) {

                $files = (array) $value;
                foreach ($files as $file) {
                    if ($basedir) $file = $basedir.'/'.$file;
                    $schema = Util::mergeRecursive($this->loadSchema($file), $schema);
                }
                unset($schema[$keyword]);

            } elseif (is_string($value) && starts_with($value, $keyword.':')) {

                $file = substr($value, strlen($keyword.':'));
                if ($basedir) $file = $basedir.'/'.$file;
                $schema[$key] = $this->loadSchema($file);

            } elseif(is_array($value)) {

                $schema[$key] = $this->resolveIncludesSchema($value, $basedir);

            }
        }
        return $schema;
    }

    /**
     * Resolve +extends from array schema
     *
     * @param array $schema
     * @param array $root
     * @return array
     */
    protected function resolveExtendsSchema(array $schema, array $root = null)
    {
        if (!$root) {
            $root = $schema;
        }

        $keyword = '+extends';
        foreach ($schema as $key => $value) {
            if ($key === $keyword) {
                $extends = (array) $value;
                foreach ($extends as $extendPath) {
                    if (!array_has($root, $extendPath)) {
                        throw new InvalidSchemaException("Cannot extend '{$extendPath}'. Key '{$extendPath}' is not defined in your schema.");
                    }

                    $valuesToExtend = array_get($root, $extendPath);
                    if (!is_array($valuesToExtend)) {
                        throw new InvalidSchemaException("Cannot extend '{$extendPath}'. Value of '{$extendPath}' is not an array.");
                    }

                    $schema = Util::mergeRecursive($valuesToExtend, $schema);
                }
                unset($schema[$keyword]);
            } elseif (is_array($value)) {
                $schema[$key] = $this->resolveExtendsSchema($value, $root);
            }
        }

        return $schema;
    }

    /**
     * Resolve ${key} from array schema
     *
     * @param array $schema
     * @param array $root
     * @return array
     */
    protected function resolveVariables(array $schema, array $root = null, array $fileSchema = null)
    {
        if (!$root) {
            $root = &$schema;
        }

        if (isset($schema[self::KEY_FILE])) {
            $fileSchema = &$schema;
        }

        $rkey = "[a-z0-9_-]+";
        $varRegex = "/\\$\{(?<var>(?<this>this\.)?(?<key>$rkey(\.$rkey)*))}/i";

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                $schema[$key] = $this->resolveVariables($value, $root, $fileSchema);
            }

            if (!is_string($value)) continue;

            preg_match_all($varRegex, $value, $matches);

            foreach ($matches['var'] as $i => $var) {
                $keyVar = $matches['key'][$i];
                $match = $matches[0][$i];
                $isThis = (bool) $matches['this'][$i];

                $runningPath = realpath('').'/';

                if ($isThis && !array_has($fileSchema, $keyVar)) {
                    $schemaFile = str_replace($runningPath, '', $fileSchema[self::KEY_FILE]);
                    throw new InvalidSchemaException("'{$keyVar}' is undefined in '{$schemaFile}'.");
                }

                if (!$isThis && !array_has($root, $keyVar)) {
                    $schemaFile = str_replace($runningPath, '', $root[self::KEY_FILE]);
                    throw new InvalidSchemaException("'{$keyVar}' is undefined in your schema.");
                }

                $varValue = $isThis ? array_get($fileSchema, $keyVar) : array_get($root, $keyVar);
                $schema[$key] = str_replace($match, $varValue, $schema[$key]);
            }
        }

        return $schema;
    }

}
