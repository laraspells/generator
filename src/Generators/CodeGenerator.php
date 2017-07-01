<?php

namespace LaraSpell\Generators;

class CodeGenerator extends BaseGenerator
{

    protected $lines = [];

    public function ln($count = 1)
    {
        foreach(range(1, $count) as $n) {
            $this->lines[] = "";
        }
    }

    public function addStatements($code)
    {
        return $this->formatCode($code);
    }

    public function formatCode($code)
    {
        $this->lines = array_merge($this->lines, $this->parseLines($code));
    }

    public function generateLines()
    {
        return $this->lines;
    }

}
