<?php

namespace LaraSpells\Generator;

use InvalidArgumentException;

class Stub
{

    protected $content;

    protected $data = [];

    public function __construct($content, array $data = [])
    {
        $this->content = $content;
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function set($key, $value)
    {
        return array_set($this->data, $key, $value);
    }

    public function get($key)
    {
        return array_get($this->data, $key);
    }

    public function render(array $data = [])
    {
        $data = array_merge($this->data, $data);
        $content = $this->getContent();
        preg_match_all("/\{\? (?<key>[a-zA-Z0-9_.-]+) \?\}/", $content, $matches);
        foreach($matches['key'] as $key) {
            if (array_has($data, $key)) {
                $value = array_get($data, $key);
                $content = $this->replaceWithIndents($content, $key, $value);
                $content = $this->replace($content, $key, $value);
            }
        }
        return $content;
    }

    protected function replaceWithIndents($content, $key, $value)
    {
        $stubKey = preg_quote("{? {$key} ?}");
        $lines = preg_split("/\n\r?/", $value);
        preg_match_all("/(?<indent>\n(\r)?[\t ]+){$stubKey}/", $content, $matches);
        foreach($matches[0] as $m) {
            $indent = preg_replace("/".$stubKey."/", "", $m);
            $content = str_replace($m, $indent.implode($indent, $lines), $content);
        }
        return $content;
    }

    protected function replace($content, $key, $value)
    {
        $stubKey = "{? {$key} ?}";
        return str_replace($stubKey, $value, $content);
    }

}
