<?php

namespace LaraSpells\Generator\Generators;

use Closure;

class InterfaceGenerator extends ClassGenerator
{

    public function generateLines()
    {
        $className = $this->getClassName();
        $uses = $this->getUsedClasses();

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
        $classDefinition = "interface {$className}";
        $lines[] = $classDefinition;
        $lines[] = "{"; // opening class bracket

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

}
