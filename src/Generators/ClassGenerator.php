<?php

namespace LaraSpells\Generator\Generators;

use Closure;
use ReflectionClass;

class ClassGenerator extends BaseGenerator
{
    use Concerns\Docblockable;

    protected $className;
    protected $namespace;
    protected $uses = [];
    protected $traits = [];
    protected $properties = [];

    protected $isAbstract = false;

    protected $parent;
    protected $implements = [];
    protected $methods = [];

    public function __construct($class)
    {
        $this->setClassName($class);
    }

    /**
     * Set class namespace.
     *
     * @param  string $namespace
     * @return void
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Get class namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get used classes.
     *
     * @return array
     */
    public function getUsedClasses()
    {
        return $this->uses;
    }

    /**
     * Get used traits.
     *
     * @return array
     */
    public function getUsedTraits()
    {
        return $this->traits;
    }

    /**
     * Set parent class name.
     *
     * @param  string $class
     * @return void
     */
    public function setParentClass($class)
    {
        $this->parent = $class;
    }

    /**
     * Get parent class name.
     *
     * @return string
     */
    public function getParentClass()
    {
        return $this->parent;
    }

    /**
     * Add used class.
     *
     * @param  string $usedClass
     * @param  string $alias
     * @return void
     */
    public function useClass($usedClass, $alias = null)
    {
        $this->uses[ltrim($usedClass, "\\")] = $alias;
    }

    /**
     * Add used trait.
     *
     * @param  string $usedTrait
     * @return void
     */
    public function useTrait($usedTrait)
    {
        if (!in_array($usedTrait, $this->traits)) {
            $this->traits[] = $usedTrait;
        }
    }

    /**
     * Add class property.
     *
     * @param  string $name
     * @param  string $type
     * @param  string $visibility
     * @param  mixed $initialValue
     * @param  string $description
     * @param  bool $static
     * @return void
     */
    public function addProperty($name, $type, $visibility, $initialValue = null, $description = null, $static = false)
    {
        $this->properties[$name] = [
            'type' => $type,
            'visibility' => $visibility,
            'initialValue' => $initialValue,
            'description' => $description,
            'static' => $static
        ];
    }

    /**
     * Get registered properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set class is abstract (true) or not (false).
     *
     * @return void
     */
    public function setAbstract($bool)
    {
        $this->isAbstract = $bool;
    }

    /**
     * Check if class is abstract class.
     *
     * @return bool
     */
    public function isAbstract()
    {
        return (bool) $this->isAbstract;
    }

    /**
     * Set class name (can be using namespace).
     *
     * @param  string $class
     * @return void
     */
    public function setClassName($class)
    {
        list($namespace, $className) = $this->parseClassNamespace($class);
        $this->className = $className;
        if ($namespace) {
            $this->setNamespace($namespace);
        }
    }

    /**
     * Get class name without namespace.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Add new method
     *
     * @param   string $method
     * @return  LaraSpells\Generator\Generators\MethodGenerator
     */
    public function addMethod($method)
    {
        $methodGenerator = new MethodGenerator($method);
        $methodGenerator->setClassGenerator($this);
        $this->methods[$method] = $methodGenerator;
        return $methodGenerator;
    }

    /**
     * Remove method
     *
     * @param  string $method
     * @return null
     */
    public function removeMethod($method)
    {
        if (isset($this->methods[$method])) {
            unset($this->methods[$method]);
        }
    }

    /**
     * Check if class has given method
     *
     * @param  string $method
     * @return bool
     */
    public function hasMethod($method)
    {
        return isset($this->methods[$method]);
    }

    /**
     * Get method by name
     *
     * @param  string $method
     * @return null|LaraSpells\Generator\Generators\MethodGenerator
     */
    public function getMethod($method)
    {
        return $this->hasMethod($method)? $this->methods[$method] : null;
    }

    /**
     * Get registered methods
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Add or modify method.
     *
     * @param  string $method
     * @param  Closure $callback
     * @return void
     */
    public function method($method, Closure $callback)
    {
        $method = $this->getMethod($method) ?: $this->addMethod($method);
        $callback($method);
    }

    /**
     * Add implemented interface.
     *
     * @param  $interface
     * @return void
     */
    public function addImplement($interface)
    {
        if (!in_array($interface, $this->implements)) {
            $this->implements[] = $interface;
        }
    }

    /**
     * Get implemented interfaces.
     *
     * @return array
     */
    public function getImplements()
    {
        return $this->implements;
    }

    public function generateLines()
    {
        $isAbstract = $this->isAbstract();
        $className = $this->getClassName();
        $parentClass = $this->getParentClass();
        $implements = $this->getImplements();
        $traits = $this->getUsedTraits();
        $uses = $this->getUsedClasses();
        list($uses, $parentClass, $implements, $traits) = $this->resolveUses($uses, $parentClass, $implements, $traits);

        $lines = [];
        $lines[] = "<?php";
        $lines[] = "";

        // Namespace line
        if ($namespace = $this->getNamespace()) {
            $lines[] = "namespace {$namespace};";
        }

        // Uses lines
        $lines[] = "";
        if (!empty($uses)) {
            ksort($uses);
            foreach($uses as $class => $alias) {
                $lines[] = "use {$class}".($alias? " as {$alias}" : "").";";
            }
            $lines[] = "";
        }

        // Docblock lines
        $docblock = $this->getDocblock();
        if ($docblock) {
            $lines = array_merge($lines, $docblock->generateLines());
        }

        // Class definition line
        $classDefinition = ($isAbstract? "abstract " : "")."class {$className}";
        if ($parentClass) {
            $classDefinition .= " extends {$parentClass}";
        }
        if (!empty($implements)) {
            $classDefinition .= " implements ".implode(", ", $implements);
        }
        $lines[] = $classDefinition;
        $lines[] = "{"; // opening class bracket

        // Traits lines
        $traitsLines = array_map(function($trait) {
            return "use {$trait};";
        }, $traits);
        $lines = array_merge($lines, $this->applyIndents($traitsLines, 1));

        // Properties lines
        $properties = $this->getProperties();
        foreach($properties as $property => $options) {
            $propertyLines = [];
            $lines[] = "";
            $docblock = new DocblockGenerator();
            if ($options['description']) {
                $docblock->addText($options['description']);
            }
            $docblock->addAnnotation('var', $options['type']);
            $propertyLines = array_merge($docblock->generateLines(), $this->parseLines(preg_replace("/ +/", " ", trim(implode(" ", [
                $options['visibility'],
                $options['static']? 'static' : '',
                '$'.$property,
                $options['initialValue']? '= '.$this->phpify($options['initialValue'], true) : '',
            ]))).';'));

            $lines = array_merge($lines, $this->applyIndents($propertyLines, 1));
        }

        // Methods lines
        $methods = $this->getMethods();
        foreach ($methods as $method) {
            $lines[] = "";
            $lines = array_merge($lines, $this->applyIndents($method->generateLines(), 1));
        }

        if (!empty($methods)) {
            $lines[] = "";
        }

        $lines[] = "}"; // closing class bracket
        $lines[] = "";
        return $lines;
    }

    /**
     * Resolve uses lines
     *
     * @param array $uses
     * @param string $parentClass
     * @param array $implements
     * @param array $traits
     * @return array
     */
    protected function resolveUses(array $uses, $parentClass, array $implements, array $traits)
    {
        if ($parentClass) {
            list($namespace, $className) = $this->parseClassNamespace($parentClass);
            if ($namespace) {
                $uses[$namespace."\\".$className] = null;
            }
            $parentClass = $className;
        }

        foreach($traits as $i => $trait) {
            list($namespace, $className) = $this->parseClassNamespace($trait);
            $traits[$i] = $className;
            if ($namespace) {
                $uses[$namespace."\\".$className] = null;
            }
        }

        foreach($implements as $i => $interface) {
            list($namespace, $className) = $this->parseClassNamespace($interface);
            $implements[$i] = $className;
            if ($namespace) {
                $uses[$namespace."\\".$className] = null;
            }
        }

        return [$uses, $parentClass, $implements, $traits];
    }

    /**
     * Add methods from reflection methods prefixed 'method'
     *
     * @param mixed $object
     * @return void
     */
    public function addMethodsFromReflection($object = null)
    {
        $reflection = new ReflectionClass($object ?: $this);
        $methods = array_filter($reflection->getMethods(), function($method) {
            $name = $method->getName();
            return (bool) preg_match("/^setMethod[A-Z]/", $name);
        });

        foreach($methods as $reflectionMethod) {
            // Remove 'method' from 'methodName'
            $name = camel_case(substr($reflectionMethod->getName(), 9));
            if (strtolower($name) == 'construct') {
                $name = '__construct';
            }
            $method = $this->addMethod($name);
            $this->{$reflectionMethod->getName()}($method);
        }
    }

}
