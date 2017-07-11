<?php

namespace LaraSpell\Commands\Concerns;

use InvalidArgumentException;
use LaraSpell\Generators\BaseGenerator;
use LaraSpell\Generators\ClassGenerator;
use LaraSpell\Generators\ControllerGenerator;
use LaraSpell\Generators\CreateRequestGenerator;
use LaraSpell\Generators\MigrationGenerator;
use LaraSpell\Generators\ModelGenerator;
use LaraSpell\Generators\RepositoryClassGenerator;
use LaraSpell\Generators\RepositoryInterfaceGenerator;
use LaraSpell\Generators\RouteGenerator;
use LaraSpell\Generators\ServiceProviderGenerator;
use LaraSpell\Generators\UpdateRequestGenerator;
use LaraSpell\Generators\ViewCreateGenerator;
use LaraSpell\Generators\ViewDetailGenerator;
use LaraSpell\Generators\ViewEditGenerator;
use LaraSpell\Generators\ViewListGenerator;
use LaraSpell\Template;

trait GeneratorBinder
{

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
     * @return LaraSpell\Generators\BaseGenerator
     */
    public function makeGenerator($class, array $params = [])
    {
        $this->validateBindableGenerator($class);
        if ($params) {
            return app()->makeWith($class, $params);
        } else {
            return app($class);
        }
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
            if (!is_subclass_of($generatorClass, $parentClass)) {
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
            ControllerGenerator::class          => ClassGenerator::class,
            MigrationGenerator::class           => ClassGenerator::class,
            ModelGenerator::class               => ClassGenerator::class,
            RepositoryClassGenerator::class     => ClassGenerator::class,
            RepositoryInterfaceGenerator::class => ClassGenerator::class,
            ServiceProviderGenerator::class     => ClassGenerator::class,
            CreateRequestGenerator::class       => ClassGenerator::class,
            UpdateRequestGenerator::class       => ClassGenerator::class,
            ViewCreateGenerator::class          => BaseGenerator::class,
            ViewDetailGenerator::class          => BaseGenerator::class,
            ViewEditGenerator::class            => BaseGenerator::class,
            ViewListGenerator::class            => BaseGenerator::class,
            RouteGenerator::class               => RouteGenerator::class,
        ];
    }
}
