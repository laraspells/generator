<?php

use PHPUnit\Framework\TestCase;
use LaraSpells\Generator\Generators\InterfaceGenerator;

class InterfaceGeneratorTest extends TestCase
{

    public function testGenerate()
    {
        $generator = new InterfaceGenerator('Foo\Bar\Baz\QuxInterface');
        $generator->setDocblock(function($docblock) {
            $docblock->addText("Interface description here");
            $docblock->addAnnotation('author', 'John Doe <johndoe@mail.com>');
            $docblock->addAnnotation('created', '20/12/2017');
        });

        $foo = $generator->addMethod('foo');
        $foo->setDocblock(function($docblock) {
            $docblock->addText("Method foo description");
            $docblock->addParam('a', 'string');
            $docblock->addParam('b', 'array');
            $docblock->setReturn('array');
        });
        $foo->addArgument('a');
        $foo->addArgument('b', 'array', []);

        $bar = $generator->addMethod('bar');
        $bar->setStatic(true);
        $bar->setVisibility('protected');
        $bar->setDocblock(function($docblock) {
            $docblock->addText("Method bar description");
            $docblock->addParam('a', 'Illuminate\Http\Request');
            $docblock->addParam('b', 'Closure');
        });
        $bar->addArgument('a', 'Illuminate\Http\Request');
        $bar->addArgument('b', 'Closure', null);

        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/interface.txt');
        $this->assertEquals($result, $assert);
    }

}
