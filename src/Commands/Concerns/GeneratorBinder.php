<?php

namespace LaraSpells\Generator\Commands\Concerns;

use InvalidArgumentException;
use LaraSpells\Generator\Generators\BaseGenerator;
use LaraSpells\Generator\Generators\ClassGenerator;
use LaraSpells\Generator\Generators\ControllerGenerator;
use LaraSpells\Generator\Generators\CreateRequestGenerator;
use LaraSpells\Generator\Generators\MigrationGenerator;
use LaraSpells\Generator\Generators\ModelGenerator;
use LaraSpells\Generator\Generators\RouteGenerator;
use LaraSpells\Generator\Generators\ServiceProviderGenerator;
use LaraSpells\Generator\Generators\UpdateRequestGenerator;
use LaraSpells\Generator\Generators\ViewCreateGenerator;
use LaraSpells\Generator\Generators\ViewDetailGenerator;
use LaraSpells\Generator\Generators\ViewEditGenerator;
use LaraSpells\Generator\Generators\ViewListGenerator;
use LaraSpells\Generator\Template;

trait GeneratorBinder
{

    protected $generators = [];

    /**
     * Bind generator class.
     *
     * @param  string $class
     * @param  string $generatorClass
     * @return void
     */
    public function bindGenerator($class, $generatorClass)
    {
        $this->validateBindableGenerator($class, $generatorClass);
        app()->bind($class, $generatorClass);
    }

    /**
     * Make generator instance.
     *
     * @param  string $class
     * @param  array $params
     * @return LaraSpells\Generator\Generators\BaseGenerator
     */
    public function makeGenerator($class, array $params = [])
    {
        $this->validateBindableGenerator($class);
        return app($class, $params);
    }

    /**
     * Validate bindable generator.
     *
     * @param  string $class
     * @param  string $generatorClass
     * @return void
     */
    protected function validateBindableGenerator($class, $generatorClass = null)
    {
        $bindableGenerators = $this->getBindableGenerators();
        if (!isset($bindableGenerators[$class])) {
            throw new InvalidArgumentException("Class '{$class}' is not bindable generator.");
        }

        if ($generatorClass) {
            $parentClass = $bindableGenerators[$class];
            if (is_object($generatorClass)) {
                $generatorClass = get_class($generatorClass);
            }

            if ($generatorClass != $parentClass AND !is_subclass_of($generatorClass, $parentClass)) {
                throw new InvalidArgumentException("Class '{$class}' must be subclass of '{$parentClass}'.");
            }
        }
    }

    /**
     * Get bindable generator classes
     *
     * @return array
     */
    protected function getBindableGenerators()
    {
        return [
            ControllerGenerator::class          => ControllerGenerator::class,
            MigrationGenerator::class           => MigrationGenerator::class,
            ModelGenerator::class               => ModelGenerator::class,
            ServiceProviderGenerator::class     => ServiceProviderGenerator::class,
            CreateRequestGenerator::class       => CreateRequestGenerator::class,
            UpdateRequestGenerator::class       => UpdateRequestGenerator::class,
            ViewCreateGenerator::class          => BaseGenerator::class,
            ViewDetailGenerator::class          => BaseGenerator::class,
            ViewEditGenerator::class            => BaseGenerator::class,
            ViewListGenerator::class            => BaseGenerator::class,
            RouteGenerator::class               => RouteGenerator::class,
        ];
    }

    public function hasGeneratorInstance($class)
    {
        return isset($this->generators[$class]);
    }

    public function setGeneratorInstance($class, $instance)
    {
        $this->validateBindableGenerator($class, $instance);
        $this->generators[$class] = $instance;
    }

    public function getOrMakeGeneratorInstance($class)
    {
        if (!$this->hasGeneratorInstance($class)) {
            $this->generators[$class] = $this->makeGenerator($class);
        }

        return $this->generators[$class];
    }

    public function getGeneratorProvider()
    {
        return $this->getOrMakeGeneratorInstance(ServiceProviderGenerator::class);
    }

    public function getGeneratorController()
    {
        return $this->getOrMakeGeneratorInstance(ControllerGenerator::class);
    }

    public function getGeneratorModel()
    {
        return $this->getOrMakeGeneratorInstance(ModelGenerator::class);
    }

    public function getGeneratorViewList()
    {
        return $this->getOrMakeGeneratorInstance(ViewListGenerator::class);
    }

    public function getGeneratorViewDetail()
    {
        return $this->getOrMakeGeneratorInstance(ViewDetailGenerator::class);
    }

    public function getGeneratorViewCreate()
    {
        return $this->getOrMakeGeneratorInstance(ViewCreateGenerator::class);
    }

    public function getGeneratorViewEdit()
    {
        return $this->getOrMakeGeneratorInstance(ViewEditGenerator::class);
    }

}
