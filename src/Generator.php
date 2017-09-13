<?php

namespace LaraSpell;

use InvalidArgumentException;
use LaraSpell\Exceptions\InvalidSchemaException;
use LaraSpell\Generators\CodeGenerator;
use LaraSpell\Generators\ControllerGenerator;
use LaraSpell\Generators\CreateRequestGenerator;
use LaraSpell\Generators\MigrationGenerator;
use LaraSpell\Generators\ModelGenerator;
use LaraSpell\Generators\UpdateRequestGenerator;
use LaraSpell\Generators\ViewCreateGenerator;
use LaraSpell\Generators\ViewDetailGenerator;
use LaraSpell\Generators\ViewEditGenerator;
use LaraSpell\Generators\ViewListGenerator;
use LaraSpell\Schema\Schema;
use LaraSpell\Schema\Table;
use Symfony\Component\Yaml\Yaml;

class Generator
{

    const TEMPLATE_INIT_FILE = 'init.php';

    protected $schemaResolver;
    protected $template;
    protected $schema;
    protected $schemaFile;

    protected $generatorMigration           = 'LaraSpell\Generators\MigrationGenerator';
    protected $generatorController          = 'LaraSpell\Generators\ControllerGenerator';
    protected $generatorCreateRequest       = 'LaraSpell\Generators\CreateRequestGenerator';
    protected $generatorUpdateRequest       = 'LaraSpell\Generators\UpdateRequestGenerator';
    protected $generatorModel               = 'LaraSpell\Generators\ModelGenerator';
    protected $generatorViewPageList        = 'LaraSpell\Generators\ViewListGenerator';
    protected $generatorViewPageDetail      = 'LaraSpell\Generators\ViewDetailGenerator';
    protected $generatorViewFormCreate      = 'LaraSpell\Generators\ViewCreateGenerator';
    protected $generatorViewFormEdit        = 'LaraSpell\Generators\ViewEditGenerator';
    protected $generatorServiceProvider     = 'LaraSpell\Generators\ServiceProviderGenerator';

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
     * @param  LaraSpell\SchemaResolver $resolver
     * @return void
     */
    public function setSchemaResolver(SchemaResolver $resolver)
    {
        $this->schemaResolver = $resolver;
    }

    /**
     * Get schema resolver instance
     *
     * @return LaraSpell\SchemaResolver
     */
    public function getSchemaResolver()
    {
        return $this->schemaResolver;
    }

    /**
     * Get schema instance
     *
     * @return LaraSpell\Schema\Schema
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get template instance
     *
     * @return LaraSpell\Template
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
     * @param  LaraSpell\Schema\Table $table
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
     * @param  LaraSpell\Schema\Table $table
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
     * @param  LaraSpell\Schema\Table $table
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
     * @param  LaraSpell\Schema\Table $table
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
     * @param  LaraSpell\Schema\Table $table
     * @return void
     */
    public function generateModel(Table $table)
    {
        $generatorClass = $this->getGeneratorModel();
        $generator = new $generatorClass($table);
        return $generator->generateCode();
    }

    /**
     * Set generator ViewPageList
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewPageList($class)
    {
        $this->assertClassOrSubClass($class, ViewListGenerator::class);
        $this->generatorViewPageList = $class;
    }

    /**
     * Get Generator ViewPageList
     *
     * @return string
     */
    public function getGeneratorViewPageList()
    {
        return $this->generatorViewPageList;
    }

    /**
     * Generate ViewPageList
     *
     * @param  LaraSpell\Schema\Table $table
     * @return void
     */
    public function generateViewPageList(Table $table)
    {
        $generatorClass = $this->getGeneratorViewPageList();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('page-list.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ViewPageDetail
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewPageDetail($class)
    {
        $this->assertClassOrSubClass($class, ViewDetailGenerator::class);
        $this->generatorViewPageDetail = $class;
    }

    /**
     * Get Generator ViewPageDetail
     *
     * @return string
     */
    public function getGeneratorViewPageDetail()
    {
        return $this->generatorViewPageDetail;
    }

    /**
     * Generate ViewPageDetail
     *
     * @param  LaraSpell\Schema\Table $table
     * @return void
     */
    public function generateViewPageDetail(Table $table)
    {
        $generatorClass = $this->getGeneratorViewPageDetail();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('page-detail.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ViewFormCreate
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewFormCreate($class)
    {
        $this->assertClassOrSubClass($class, ViewCreateGenerator::class);
        $this->generatorViewFormCreate = $class;
    }

    /**
     * Get Generator ViewFormCreate
     *
     * @return string
     */
    public function getGeneratorViewFormCreate()
    {
        return $this->generatorViewFormCreate;
    }

    /**
     * Generate ViewFormCreate
     *
     * @param  LaraSpell\Schema\Table $table
     * @return void
     */
    public function generateViewFormCreate(Table $table)
    {
        $generatorClass = $this->getGeneratorViewFormCreate();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('form-create.stub'));
        return $generator->generateCode();
    }

    /**
     * Set generator ViewFormEdit
     *
     * @param string $class
     * @return void
     */
    public function setGeneratorViewFormEdit($class)
    {
        $this->assertClassOrSubClass($class, ViewEditGenerator::class);
        $this->generatorViewFormEdit = $class;
    }

    /**
     * Get Generator ViewFormEdit
     *
     * @return string
     */
    public function getGeneratorViewFormEdit()
    {
        return $this->generatorViewFormEdit;
    }

    /**
     * Generate ViewFormEdit
     *
     * @param  LaraSpell\Schema\Table $table
     * @return void
     */
    public function generateViewFormEdit(Table $table)
    {
        $generatorClass = $this->getGeneratorViewFormEdit();
        $generator = new $generatorClass($table, $this->getTemplate()->getStubContent('form-edit.stub'));
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
     * @param  LaraSpell\Schema\Table $table
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
