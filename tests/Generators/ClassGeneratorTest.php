<?php

use PHPUnit\Framework\TestCase;
use LaraSpells\Generator\Generators\ClassGenerator;

class ClassGeneratorTest extends TestCase
{

    public function testGenerate()
    {
        $generator = new ClassGenerator('Foo\Bar\Baz\Qux');
        $generator->setParentClass('Illuminate\Database\Eloquent\Model');
        $generator->setDocblock(function($docblock) {
            $docblock->addText("Class description here");
            $docblock->addAnnotation('author', 'John Doe <johndoe@mail.com>');
            $docblock->addAnnotation('created', '20/12/2017');
        });
        $generator->useClass('Datetime');
        $generator->useClass('Faker\Factory', 'Faker');
        $generator->addImplement('App\Contracts\X');
        $generator->addImplement('App\Contracts\Y');
        $generator->setAbstract(true);
        $generator->useTrait('Namespace\To\Traits\A');
        $generator->useTrait('Namespace\To\Traits\B');
        $generator->useTrait('Namespace\To\Traits\C');
        $generator->addProperty('thing', 'string', 'public', null, 'Just a thing');
        $generator->addProperty('arr', 'array', 'protected', ['a', 'b', 'c'], 'An array values');

        $foo = $generator->addMethod('foo');
        $foo->setDocblock(function($docblock) {
            $docblock->addText("Method foo description");
            $docblock->addParam('a', 'string');
            $docblock->addParam('b', 'array');
            $docblock->setReturn('array');
        });
        $foo->addArgument('a');
        $foo->addArgument('b', 'array', []);
        $foo->addCode("
            return array_filter(\$b, function(\$value) use (\$a) {
                \$a = preg_quote(\$a);
                return (bool) preg_match(\"/{\$a}/i\", \$value);
            });
        ");

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
        $bar->addCode("
            \$a->validate([
                'x' => 'required|numeric',
                'y' => 'required'
            ]);

            if (\$b) {
                \$b(\$a);
            }
        ");

        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/class.txt');
        $this->assertEquals($result, $assert);
    }

}
