<?php

namespace LaraSpell\Generators;

use Closure;
use ReflectionClass;

class ClassGenerator extends BaseGenerator
{

    protected $className;
    protected $namespace;
    protected $uses = [];
    protected $traits = [];
    protected $properties = [];

    protected $isAbstract = false;

    protected $docblock;
    protected $parent;
    protected $implements = [];
    protected $methods = [];

    public function __construct($class)
    {
        $this->setClassName($class);
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getUsedClasses()
    {
        return $this->uses;
    }

    public function getTraits()
    {
        return $this->traits;
    }

    public function setParentClass($class)
    {
        $this->parent = $class;
    }

    public function getParentClass()
    {
        return $this->parent;
    }

    public function useClass($usedClass, $alias = null)
    {
        $this->uses[ltrim($usedClass, "\\")] = $alias;
    }

    public function useTrait($usedTrait)
    {
        $this->traits[] = $usedTrait;
    }

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

    public function getProperties()
    {
        return $this->properties;
    }

    public function setAbstract($bool)
    {
        $this->isAbstract = $bool;
    }

    public function isAbstract()
    {
        return (bool) $this->isAbstract;
    }

    public function setClassName($class)
    {
        list($namespace, $className) = $this->parseClassNamespace($class);
        $this->className = $className;
        if ($namespace) {
            $this->setNamespace($namespace);
        } 
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function addMethod($method)
    {
        $methodGenerator = new MethodGenerator($method);
        $methodGenerator->setClassGenerator($this);
        $this->methods[$method] = $methodGenerator;
        return $methodGenerator;
    }

    public function getMethod($method)
    {
        return isset($this->methods[$method])? $this->methods[$method] : null;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function setDocblock(Closure $callback)
    {
        $this->docblock = new DocblockGenerator;
        $callback($this->docblock);
    }

    public function getDocblock()
    {
        return $this->docblock;
    }

    public function addImplement($class)
    {
        $this->implements[] = $class;
    }

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
        $traits = $this->getTraits();
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
            return starts_with($name, 'method');
        });

        foreach($methods as $reflectionMethod) {
            // Remove 'method' from 'methodName'
            $name = camel_case(substr($reflectionMethod->getName(), 6));
            if (strtolower($name) == 'construct') {
                $name = '__construct';
            }
            $method = $this->addMethod($name);
            $this->{$reflectionMethod->getName()}($method);
        }
    }

}
