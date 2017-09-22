<?php

namespace LaraSpells\Generator\Generators;

abstract class BaseGenerator
{

    protected $indent = "    "; // 4 space
    protected $nl = "\n";

    protected $selfClosingTags = [
        'area',
        'base',
        'br',
        'col',
        'command',
        'embed',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    abstract public function generateLines();

    public function setIndent($indent)
    {
        $this->indent = $indent;
    }

    public function getIndent()
    {
        return $this->indent;
    }

    public function generateCode()
    {
        return implode($this->nl, (array) $this->generateLines());
    }

    public function applyIndents(array $lines, $countIndent)
    {
        $indent = str_repeat($this->getIndent(), $countIndent);
        return array_map(function($line) use ($indent) {
            return $line? $indent.$line : "";
        }, $lines);
    }

    protected function parseClassNamespace($class)
    {
        $exp = explode("\\", trim($class, "\\"));
        $className = array_pop($exp);
        return [implode("\\", $exp), $className];
    }

    public function formatCode($code)
    {
        $lines = $this->parseLines($code);
        return implode($this->getNewLine(), $lines);
    }

    protected function parseLines($code)
    {
        $code = trim($code);
        $indent = $this->getIndent();
        $tab = 0;
        $lines = preg_split("/\n(\r+)?/", $code);
        $tags = [];

        // Correcting indentations
        foreach($lines as $i => $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            if ($this->isHtml($line)) {
                $lastTag = $tags? $tags[count($tags) - 1] : null;
                if ($lastTag AND $this->isClosingTag($line, $lastTag)) {
                    array_pop($tags);
                    $tab--;
                }

                $lines[$i] = str_repeat($indent, $tab).$line;

                list($tagName, $isOpeningTag) = $this->parseHtmlLineInfo($line);
                if ($tagName AND $isOpeningTag AND !in_array($tagName, $this->selfClosingTags)) {
                    $tags[] = $tagName;
                    $tab++;
                }
            } else {
                if (starts_with($line, ["}", "]", ")"]) AND $tab > 0) {
                    $tab--;
                }

                if (starts_with($line, '->')) {
                    $lines[$i] = str_repeat($indent, $tab + 1).$line;
                } else {
                    $lines[$i] = str_repeat($indent, $tab).$line;
                }

                if (ends_with($line, ["{", "[", "("])) {
                    $tab++;
                }
            }
        }

        return $lines;
    }

    protected function isHtml($line)
    {
        return starts_with($line, "<") AND ends_with($line, ">");
    }

    protected function isClosingTag($line, $tagName)
    {
        return starts_with($line, "</{$tagName}>");
    }

    protected function parseHtmlLineInfo($line)
    {
        $regex = "/^<(?<tag>[a-zA-Z_-]+)/";
        preg_match($regex, $line, $match);
        if (empty($match['tag'])) {
            return [null, false];
        } else {
            $tagName = $match['tag'];
            return [$tagName, !ends_with($line, "</{$tagName}>")];
        }
    }

    /**
     * Format value into PHP code
     * For examples:
     * 100      -> "100"
     * "foo"    -> "\"foo\""
     * null     -> "null"
     * false    -> "false"
     * [1,2,3]  -> "[1,2,3]"
     *
     * @param mixed $value
     * @param bool $pretty
     * @return string
     */
    public function phpify($value, $pretty = false)
    {
        if (is_array($value) OR $value instanceof \stdClass) {
            $array = (array) $value;
            $assoc = $this->isAssoc($array);
            $keys = array_keys($array);
            $nl = $this->getNewLine();
            $opening = "[".($pretty? $nl : "");
            $closing = ($pretty? $nl : "")."]";
            $code = $opening;
            foreach($array as $k => $v) {
                $isLast = $k == $keys[count($keys) - 1];
                if (is_string($k)) $k = "'{$k}'";
                $v = $this->phpify($v, $pretty);
                $code .= $assoc? "{$k} => {$v}" : $v;
                if (!$isLast) {
                    $code .= ", ".($pretty? $nl : "");
                }
            }
            $code .= $closing;
            if ($value instanceof \stdClass) {
                $code = "(object) ".$code;
            }

            $gen = new CodeGenerator;
            $gen->addCode($code);
            $str = $gen->generateCode();
        } elseif($this->isEval($value)) {
            $str = $this->getCodeInsideEval($value);
        } else {
            $str = json_encode($value, $pretty? JSON_PRETTY_PRINT : null);
        }

        return $str;
    }

    public function getNewLine()
    {
        return $this->nl;
    }

    public function setNewLine($nl)
    {
        $this->nl = $nl;
    }

    protected function isAssoc($value)
    {
        if (!is_array($value)) return false;
        $keys = array_keys($value);
        $len = count($keys);
        $indexedKeys = range(0, $len - 1);
        return $keys !== $indexedKeys;
    }

    protected function isEval($str)
    {
        return starts_with($str, "eval(\"") AND ends_with($str, "\")");
    }

    protected function getCodeInsideEval($str)
    {
        if ($this->isEval($str)) {
            return preg_replace("/(^eval\(\"|\"\)$)/", "", $str);
        } else {
            return $str;
        }
    }

}
