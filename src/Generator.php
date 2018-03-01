<?php

namespace LaraSpells\Generator;

use InvalidArgumentException;
use LaraSpells\Generator\Exceptions\InvalidSchemaException;
use LaraSpells\Generator\Generators\CodeGenerator;
use LaraSpells\Generator\Generators\ControllerGenerator;
use LaraSpells\Generator\Generators\CreateRequestGenerator;
use LaraSpells\Generator\Generators\MigrationGenerator;
use LaraSpells\Generator\Generators\ModelGenerator;
use LaraSpells\Generator\Generators\UpdateRequestGenerator;
use LaraSpells\Generator\Generators\ViewCreateGenerator;
use LaraSpells\Generator\Generators\ViewDetailGenerator;
use LaraSpells\Generator\Generators\ViewEditGenerator;
use LaraSpells\Generator\Generators\ViewListGenerator;
use LaraSpells\Generator\Schema\Schema;
use LaraSpells\Generator\Schema\Table;
use Symfony\Component\Yaml\Yaml;

class Generator
{

    const TEMPLATE_INIT_FILE = 'init.php';

    protected $schemaResolver;
    protected $template;
    protected $schema;
    protected $schemaFile;

    protected $generatorMigration           = 'LaraSpells\Generator\Generators\MigrationGenerator';
    protected $generatorController          = 'LaraSpells\Generator\Generators\ControllerGenerator';
    protected $generatorCreateRequest       = 'LaraSpells\Generator\Generators\CreateRequestGenerator';
    protected $generatorUpdateRequest       = 'LaraSpells\Generator\Generators\UpdateRequestGenerator';
    protected $generatorModel               = 'LaraSpells\Generator\Generators\ModelGenerator';
    protected $generatorViewIndex           = 'LaraSpells\Generator\Generators\ViewListGenerator';
    protected $generatorViewShow            = 'LaraSpells\Generator\Generators\ViewDetailGenerator';
    protected $generatorViewCreate          = 'LaraSpells\Generator\Generators\ViewCreateGenerator';
    protected $generatorViewEdit            = 'LaraSpells\Generator\Generators\ViewEditGenerator';
    protected $generatorServiceProvider     = 'LaraSpells\Generator\Generators\ServiceProviderGenerator';

    public function __construct($schemaFile)
    {
        $schemaFile;
        if (!is_file($schemaFile)) {
            throw new InvalidArgumentException("Schema file '{$schemaFile}' not found.");
        }
        $schemaFileContent = file_get_contents($schemaFile);
        $arraySchema = Yaml::parse($schemaFileContent);

        $this->schemaFile = $schemaFile;
        $this->setSchemaResolver(new SchemaResolver);
        $this->initTemplate($arraySchema);
        $this->initSchema($arraySchema);
    }

    /**
     * Set schema resolver
     *
     * @param  LaraSpells\Generator\SchemaResolver $resolver
     * @return void
     */
    public function setSchemaResolver(SchemaResolver $resolver)
    {
        $this->schemaResolver = $resolver;
    }

    /**
     * Get schema resolver instance
     *
     * @return LaraSpells\Generator\SchemaResolver
     */
    public function getSchemaResolver()
    {
        return $this->schemaResolver;
    }

    /**
     * Get schema instance
     *
     * @return LaraSpells\Generator\Schema\Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get template instance
     *
     * @return LaraSpells\Generator\Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set generator Migration
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorMigration($class)
    {
        $this->assertClassOrSubClass($class, MigrationGenerator::class);
        $this->generatorMigration = $class;
    }

    /**
     * Get Generator Migration
     *
     * @return string
     */
    public function getGeneratorMigration()
    {
        return $this->generatorMigration;
    }

    /**
     * Generate Migration
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateMigration(Table $table)
    {
        $generatorClass = $this->getGeneratorMigration();
        $generator = new $generatorClass($table);
        return $generator->generateCode();
    }

    /**
     * Set generator CreateRequest
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorCreateRequest($class)
    {
        $this->assertClassOrSubClass($class, CreateRequestGenerator::class);
        $this->generatorCreateRequest = $class;
    }

    /**
     * Get Generator CreateRequest
     *
     * @return string
     */
    public function getGeneratorCreateRequest()
    {
        return $this->generatorCreateRequest;
    }

    /**
     * Generate CreateRequest
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateCreateRequest(Table $table)
    {
        $generatorClass = $this->getGeneratorCreateRequest();
        $generator = new $generatorClass($table);
        return $generator->generateCode();
    }

    /**
     * Set generator UpdateRequest
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorUpdateRequest($class)
    {
        $this->assertClassOrSubClass($class, UpdateRequestGenerator::class);
        $this->generatorUpdateRequest = $class;
    }

    /**
     * Get Generator UpdateRequest
     *
     * @return string
     */
    public function getGeneratorUpdateRequest()
    {
        return $this->generatorUpdateRequest;
    }

    /**
     * Generate UpdateRequest
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateUpdateRequest(Table $table)
    {
        $generatorClass = $this->getGeneratorUpdateRequest();
        $generator = new $generatorClass($table);
        return $generator->generateCode();
    }

    /**
     * Set generator Controller
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorController($class)
    {
        $this->assertClassOrSubClass($class, ControllerGenerator::class);
        $this->generatorController = $class;
    }

    /**
     * Get Generator Controller
     *
     * @return string
     */
    public function getGeneratorController()
    {
        return $this->generatorController;
    }

    /**
     * Generate Controller
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateController(Table $table)
    {
        $generatorClass = $this->getGeneratorController();
        $generator = new $generatorClass($table);
        return $generator->generateCode();
    }

    /**
     * Set generator Model
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorModel($class)
    {
        $this->assertClassOrSubClass($class, ModelGenerator::class);
        $this->generatorModel = $class;
    }

    /**
     * Get Generator Model
     *
     * @return string
     */
    public function getGeneratorModel()
    {
        return $this->generatorModel;
    }

    /**
     * Generate Model
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateModel(Table $table)
    {
        $generatorClass = $this->getGeneratorModel();
        $generator = new $generatorClass($table);
        return $generator->generateCode();
    }

    /**
     * Set generator ViewIndex
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewIndex($class)
    {
        $this->assertClassOrSubClass($class, ViewListGenerator::class);
        $this->generatorViewIndex = $class;
    }

    /**
     * Get Generator ViewIndex
     *
     * @return string
     */
    public function getGeneratorViewIndex()
    {
        return $this->generatorViewIndex;
    }

    /**
     * Generate ViewIndex
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateViewIndex(Table $table)
    {
        $generatorClass = $this->getGeneratorViewIndex();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('index.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ViewShow
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewShow($class)
    {
        $this->assertClassOrSubClass($class, ViewDetailGenerator::class);
        $this->generatorViewShow = $class;
    }

    /**
     * Get Generator ViewShow
     *
     * @return string
     */
    public function getGeneratorViewShow()
    {
        return $this->generatorViewShow;
    }

    /**
     * Generate ViewShow
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateViewShow(Table $table)
    {
        $generatorClass = $this->getGeneratorViewShow();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('show.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ViewCreate
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewCreate($class)
    {
        $this->assertClassOrSubClass($class, ViewCreateGenerator::class);
        $this->generatorViewCreate = $class;
    }

    /**
     * Get Generator ViewCreate
     *
     * @return string
     */
    public function getGeneratorViewCreate()
    {
        return $this->generatorViewCreate;
    }

    /**
     * Generate ViewCreate
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateViewCreate(Table $table)
    {
        $generatorClass = $this->getGeneratorViewCreate();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('create.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ViewEdit
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewEdit($class)
    {
        $this->assertClassOrSubClass($class, ViewEditGenerator::class);
        $this->generatorViewEdit = $class;
    }

    /**
     * Get Generator ViewEdit
     *
     * @return string
     */
    public function getGeneratorViewEdit()
    {
        return $this->generatorViewEdit;
    }

    /**
     * Generate ViewEdit
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateViewEdit(Table $table)
    {
        $generatorClass = $this->getGeneratorViewEdit();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('edit.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ServiceProvider
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorServiceProvider($class)
    {
        $this->assertClassOrSubClass($class, ServiceProviderGenerator::class);
        $this->generatorServiceProvider = $class;
    }

    /**
     * Get Generator ServiceProvider
     *
     * @return string
     */
    public function getGeneratorServiceProvider()
    {
        return $this->generatorServiceProvider;
    }

    /**
     * Generate ServiceProvider
     *
     * @param  LaraSpells\Generator\Schema\Table $table
     * @return void
     */
    public function generateServiceProvider()
    {
        $generatorClass = $this->getGeneratorServiceProvider();
        $generator = new $generatorClass($this->getSchema());
        return $generator->generateCode();
    }

    /**
     * Generate configuration code
     *
     * @param  array $menu
     * @return string
     */
    public function generateConfig(array $menu)
    {
        $code = new CodeGenerator;
        $config = [
            'menu' => $menu
        ];

        $configArray = $code->phpify($config, true);
        return "<?php\n\nreturn {$configArray};\n";
    }

    protected function initTemplate(array $arraySchema)
    {
        if (!isset($arraySchema['template'])) {
            throw new InvalidSchemaException("Schema must have template.");
        }

        $templateDir = $arraySchema['template'];
        if (!is_dir($templateDir)) {
            throw new InvalidSchemaException("Template directory '{$templateDir}' not found.");
        }

        $template = new Template($templateDir);
        $this->template = $template;
        if ($template->hasFile(static::TEMPLATE_INIT_FILE)) {
            $generator = $this;
            require_once($template->getFilePath(static::TEMPLATE_INIT_FILE));
        }
    }

    protected function initSchema(array $arraySchema)
    {
        $resolver = $this->getSchemaResolver();
        $arraySchema = $resolver->resolve($arraySchema);
        $this->schema = new Schema($arraySchema);
    }

    protected function assertClassOrSubClass($class, $assert)
    {
        $class = ltrim($class, "\\");
        $assert = ltrim($assert, "\\");
        if ($class == $assert OR is_subclass_of($class, $assert)) {
            return true;
        } else {
            throw new InvalidArgumentException("Class '$class' must be class or subclass of '$assert'.");
        }
    }

}
