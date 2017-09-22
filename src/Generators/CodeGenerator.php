<?php

namespace LaraSpells\Generator\Generators;

use Closure;

class CodeGenerator extends BaseGenerator
{

    protected $codes = [];

    public function getCodes()
    {
        return $this->codes;
    }

    public function nl($count = 1)
    {
        foreach(range(1, $count) as $n) {
            $this->codes[] = [
                "label" => "nl",
                "code" => ""
            ];
        }
    }

    public function addCode($code, $label = null)
    {
        $this->codes[] = [
            'label' => $label,
            'code' => $code
        ];
    }

    public function appendCode($code, $label = null)
    {
        return $this->addCode($code, $label);
    }

    public function prependCode($code, $label = null)
    {
        array_unshift($this->codes, [
            'label' => $label,
            'code' => $code
        ]);
    }

    public function insertCodeBefore($findLabel, $code, $label = null)
    {
        $codes = [];
        $codeData = [
            'label' => $label,
            'code' => $code
        ];
        foreach($this->codes as $i => $_code) {
            if ($_code['label'] == $findLabel) {
                $codes[] = $codeData;
            }
            $codes[] = $_code;
        }
        $this->codes = $codes;
    }

    public function insertCodeAfter($findLabel, $code, $label = null)
    {
        $codes = [];
        $codeData = [
            'label' => $label,
            'code' => $code
        ];
        foreach($this->codes as $i => $_code) {
            $codes[] = $_code;
            if ($_code['label'] == $findLabel) {
                $codes[] = $codeData;
            }
        }
        $this->codes = $codes;
    }

    public function generateLines()
    {
        $lines = [];
        foreach($this->getCodes() as $code) {
            $lines = array_merge($lines, $this->parseLines($code['code']));
        }
        return $lines;
    }

    public function map($label, Closure $mapper)
    {
        foreach($this->getCodes() as $i => $code) {
            if ($code['label'] == $label) {
                $this->codes[$i] = $mapper($code['code']);
            }
        }
    }

}
