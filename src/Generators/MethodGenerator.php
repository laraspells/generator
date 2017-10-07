<?php

namespace LaraSpells\Generator\Generators;

use Closure;

class MethodGenerator extends BaseGenerator
{
    use Concerns\Docblockable;

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PROTECTED = 'protected';
    const VISIBILITY_PRIVATE = 'private';

    protected $name;
    protected $code;
    protected $visibility;
    protected $static = false;
    protected $final = false;
    protected $abstract = false;
    protected $arguments = [];
    protected $classGenerator;

    public function __construct($name)
    {
        $this->name = $name;
        $this->setVisibility(static::VISIBILITY_PUBLIC);
        $this->code = new CodeGenerator;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setVisibility($visibility)
    {
        if (!in_array($visibility, [static::VISIBILITY_PRIVATE, static::VISIBILITY_PROTECTED, static::VISIBILITY_PUBLIC])) {
            throw new InvalidArgumentException("Visibility '{$visibility}' is not valid visibility");
        }

        $this->visibility = $visibility;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }

    public function setStatic($static)
    {
        $this->static = (bool) $static;
    }

    public function isStatic()
    {
        return $this->static;
    }

    public function setFinal($final)
    {
        $this->final = (bool) $final;
    }

    public function isFinal()
    {
        return $this->final;
    }

    public function setAbstract($abstract)
    {
        $this->abstract = (bool) $abstract;
    }

    public function isAbstract()
    {
        return $this->abstract;
    }

    public function addArgument($varname, $type = null, $defaultValue = null)
    {
        $varname = ltrim($varname, '$');
        $args = func_get_args();

        if ($type AND (class_exists($type) OR str_contains($type, "\\"))) {
            list($namespace, $class) = $this->parseClassNamespace($type);
            $classGenerator = $this->getClassGenerator();
            if ($classGenerator) {
                $classGenerator->useClass($namespace."\\".$class);
                $type = $class;
            } elseif(class_exists($type)) {
                $type = "\\".$type;
            }
        }

        $this->arguments[$varname] = [
            'type' => $type,
            'default_value' => $defaultValue,
            'has_default_value' => count($args) > 2
        ];
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setClassGenerator(ClassGenerator $classGenerator)
    {
        $this->classGenerator = $classGenerator;
        $this->setIndent($classGenerator->getIndent());
    }

    public function getClassGenerator()
    {
        return $this->classGenerator;
    }

    public function isFromInterface()
    {
        $classGenerator = $this->getClassGenerator();
        return $classGenerator AND $classGenerator instanceof InterfaceGenerator;
    }

    public function generateLines()
    {
        $lines = [];

        // Docblock lines
        $docblock = $this->getDocblock();
        if ($docblock) {
            if (!$docblock->getReturn()) {
                $docblock->setReturn('void');
            }
            $lines = array_merge($lines, $docblock->generateLines());
        }

        // Method definition lines
        $abstract = $this->isAbstract();
        $final = $this->isFinal();
        $static = $this->isStatic();
        $methodName = $this->getName();
        $visibility = $this->getVisibility();
        $isFromInterface = $this->isFromInterface();
        $arguments = $this->resolveArguments($this->getArguments());
        $methodDefinition = preg_replace("/ +/", " ", trim(implode(" ", [
            $abstract? "abstract" : "",
            $final? "final" : "",
            $visibility,
            $static? "static" : "",
            "function",
            $methodName
        ])));

        if (strlen($methodDefinition."(".implode(", ", $arguments).")") > 120) {
            $lines[] = $methodDefinition."(";
            $indent = $this->getIndent();
            foreach($arguments as $i => $arg) {
                $isLast = $i === count($arguments) - 1;
                if (!$isLast) {
                    $lines[] = $indent.$arg.",";
                } else {
                    $lines[] = $indent.$arg;
                }
            }
            $lines[] = ") ".(($isFromInterface OR $abstract)? '' : '{'); // opening method
        } else {
            $lines[] = $methodDefinition."(".implode(", ", $arguments).")";
            if (!$abstract AND !$isFromInterface) {
                $lines[] = "{"; // opening method
            }
        }

        if ($abstract OR true === $isFromInterface) {
            $lines[count($lines) - 1] .= ";";
            return $lines;
        }

        // Code lines
        $code = $this->getCode();
        if ($code) {
            $lines = array_merge($lines, $this->applyIndents($code->generateLines(), 1));
        }

        $lines[] = "}"; // closing method
        return $lines;
    }

    protected function resolveArguments(array $arguments)
    {
        $args = [];
        foreach($arguments as $varname => $options) {
            $type = $options['type'];
            $hasDefaultValue = $options['has_default_value'];
            $defaultValue = $options['default_value'];
            $arg = trim("{$type} \${$varname}");
            if ($hasDefaultValue) {
                $arg .= " = ".$this->phpify($defaultValue);
            }
            $args[] = $arg;
        }
        return $args;
    }

    public function __call($method, array $args)
    {
        return call_user_func_array([$this->getCode(), $method], $args);
    }

}
