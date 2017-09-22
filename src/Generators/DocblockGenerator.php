<?php

namespace LaraSpells\Generator\Generators;

class DocblockGenerator extends BaseGenerator
{
    protected $lineLength = 80;

    protected $texts = [];
    protected $annotations = [];
    protected $params = [];
    protected $return;

    public function setLineLength($length)
    {
        $this->lineLength = $length;
    }

    public function addText($text)
    {
        $this->texts[] = $text;
    }

    public function getTexts()
    {
        return $this->texts;
    }

    public function addAnnotation($annotation, $value)
    {
        $this->annotations[] = [
            'annotation' => $annotation,
            'value' => $value
        ];
    }

    public function getAnnotations()
    {
        return $this->annotations;
    }

    public function addParam($varname, $type, $description = null)
    {
        $this->params[ltrim($varname, '$')] = [
            'type' => $type,
            'description' => $description
        ];
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setReturn($return)
    {
        $this->return = $return;
    }

    public function getReturn()
    {
        return $this->return;
    }

    public function generateLines()
    {
        $lines = ["/**"];
        $texts = $this->getTexts();
        foreach($texts as $text) {
            $textLines = $this->chunkWords($text, $this->lineLength);
            foreach($textLines as $line) {
                $lines[] = " * {$line}";
            }
        }

        if (!empty($texts)) {
            $lines[] = " * ";
        }

        $annotations = $this->getAnnotations();
        foreach($this->getParams() as $varname => $data) {
            extract($data);
            $annotations[] = [
                'annotation' => 'param',
                'value' => trim("{$type} \${$varname} {$description}")
            ];
        }
        $return = $this->getReturn();
        if ($return) {
            $annotations[] = [
                'annotation' => 'return',
                'value' => $return
            ];
        }

        if (!empty($annotations)) {
            $maxLengthAnnotation = max(array_map(function($data) {
                return strlen($data['annotation']);
            }, $annotations));

            foreach($annotations as $data) {
                extract($data);
                $annotation = str_pad($annotation, $maxLengthAnnotation, ' ', STR_PAD_RIGHT);
                $lines[] = " * @{$annotation} {$value}";
            }
        }

        $lines[] = " */";

        return $lines;
    }

    protected function chunkWords($text, $length)
    {
        $words = explode(" ", $text);
        $lines = [];
        $line = 0;
        foreach($words as $i => $word) {
            if (!isset($lines[$line])) {
                $lines[$line] = $word;
            } else {
                $lineText = $lines[$line];

                if (strlen($lineText.' '.$word) > $length) {
                    $line++;
                    $lines[$line] = $word;
                } else {
                    $lines[$line] .= ' '.$word;
                }
            }
        }

        return $lines;
    }

}
