<?php

use PHPUnit\Framework\TestCase;
use LaraSpells\Generator\Generators\MethodGenerator;

class MethodGeneratorTest extends TestCase
{

    public function setUp()
    {
        $generator = new MethodGenerator("foobar");
        $generator->addArgument('a');
        $generator->addArgument('b', 'array');
        $generator->addArgument('c', 'Closure', null);
        $generator->addCode("
            \$array = [
                'a' => 1,
                'b' => 2,
                'c' => [
                    'c1' => 3,
                    'c2' => 4
                ]
            ];

            \$fn = function() {
                return \"value\";
            };
        ");

        $this->generator = $generator;
    }

    public function testGeneratePublicMethod()
    {
        $generator = $this->generator;
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-public.txt');
        $this->assertEquals($result, $assert);
    }

    public function testGeneratePrivateMethod()
    {
        $generator = $this->generator;
        $generator->setVisibility(MethodGenerator::VISIBILITY_PRIVATE);
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-private.txt');
        $this->assertEquals($result, $assert);
    }

    public function testGenerateProtectedMethod()
    {
        $generator = $this->generator;
        $generator->setVisibility(MethodGenerator::VISIBILITY_PROTECTED);
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-protected.txt');
        $this->assertEquals($result, $assert);
    }

    public function testGenerateStaticMethod()
    {
        $generator = $this->generator;
        $generator->setStatic(true);
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-static.txt');
        $this->assertEquals($result, $assert);
    }

    public function testGenerateFinalStaticMethod()
    {
        $generator = $this->generator;
        $generator->setStatic(true);
        $generator->setFinal(true);
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-static-final.txt');
        $this->assertEquals($result, $assert);
    }

    public function testGenerateAbstractMethod()
    {
        $generator = $this->generator;
        $generator->setAbstract(true);
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-abstract.txt');
        $this->assertEquals($result, $assert);
    }

    public function testGenerateWithDocblock()
    {
        $generator = $this->generator;
        $generator->setDocblock(function($docblock) {
            $docblock->setLineLength(60);
            $docblock->addText("Line 1");
            $docblock->addText("1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890");
            $docblock->addAnnotation('key', 'value1');
            $docblock->addAnnotation('key2', 'value2');
            $docblock->addParam('a', 'string', 'foobar');
            $docblock->addParam('b', 'array', 'bazqux');
            $docblock->addParam('c', 'Closure', 'callback');
        });
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/method-with-docblock.txt');
        $this->assertEquals($result, $assert);
    }

}
