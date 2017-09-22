<?php

use PHPUnit\Framework\TestCase;
use LaraSpells\Generator\Generators\DocblockGenerator;

class DocblockGeneratorTest extends TestCase
{

    public function testGenerate()
    {
        $generator = new DocblockGenerator;
        $generator->setLineLength(60);
        $generator->addText("Line 1");
        $generator->addText("1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890 1234567890");
        $generator->addAnnotation('key', 'value1');
        $generator->addAnnotation('key2', 'value2');
        $generator->addParam('varString', 'string', 'foobar');
        $generator->addParam('varInt', 'int', 'bazqux');
        $generator->setReturn('type');
        $result = $generator->generateCode();
        $assert = file_get_contents(__DIR__.'/../src/results/docblock.txt');

        $this->assertEquals($result, $assert);
    }

}
