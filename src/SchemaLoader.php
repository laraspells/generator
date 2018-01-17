<?php

namespace LaraSpells\Generator;

use LaraSpells\Generator\Exceptions\InvalidSchemaException;
use LaraSpells\Generator\Schema\Field;
use LaraSpells\Generator\Util;
use Symfony\Component\Yaml\Yaml;

class SchemaLoader
{

    public static function load($file)
    {
        $file = static::resolveFile('', $file);
        $schema = static::parseYaml($file);
        $indexFiles = ['' => $file];
        static::resolveIncludes($schema, $indexFiles, dirname($file));
        static::resolveExtends($schema, [], $indexFiles);
        static::resolveVariables($schema, $indexFiles);
        return $schema;
    }

    public static function getPathKey(array $indexFiles, $path)
    {
        $filePaths = array_keys($indexFiles);
        $paths = explode('.', $path);

        do {
            $keyPath = implode('.', $paths);
            if (in_array($keyPath, $filePaths)) {
                return $keyPath;
            }
            array_pop($paths);
        } while(count($paths));

        return null;
    }

    public static function getPathFile(array $indexFiles, $path)
    {
        $pathKey = static::getPathKey($indexFiles, $path);
        return isset($indexFiles[$pathKey]) ? $indexFiles[$pathKey] : null;
    }

    public static function getPathFileSchema(array &$schema, array $indexFiles, $path)
    {
        $pathFile = static::getPathFile($indexFiles, $path);
        $pathKeys = array_keys(array_filter($indexFiles, function ($file) use ($pathFile) {
            return $pathFile == $file;
        }));

        $results = [];
        foreach ($pathKeys as $pathKey) {
            $x = explode(".", $pathKey);
            $key = array_pop($x);
            $results[$key] = array_get($schema, $pathKey);
        }
        return $results;
    }

    protected static function parseYaml($file)
    {
        $content = file_get_contents($file);
        return Yaml::parse($content);
    }

    protected static function resolveIncludes(array &$schema, array &$indexFiles, $basedir = '', $path = '')
    {
        $keyword = '+include';

        foreach ($schema as $key => $value) {
            if ($key === $keyword) {
                $files = array_map(function($file) use ($basedir) {
                    return static::resolveFile($basedir, $file);
                }, (array) $value);
                static::mergeIncludes($schema, $files, $path, $indexFiles);
                unset($schema[$keyword]);
            } elseif (is_string($value) && starts_with($value, $keyword.':')) {
                $file = trim(substr($value, strlen($keyword.':')));
                static::mergeInclude($schema, $key, static::resolveFile($basedir, $file), $path, $indexFiles);
            } elseif(is_array($value)) {
                static::resolveIncludes($value, $indexFiles, $basedir, static::mergePaths($path, $key));
                $schema[$key] = $value;
            }
        }
    }

    protected static function mergeIncludes(array &$schema, array $files, $path, array &$indexFiles)
    {
        foreach ($files as $file) {
            $arr = static::parseYaml($file);
            $basedir = dirname($file);

            static::resolveIncludes($arr, $indexFiles, $basedir, $path);

            foreach ($arr as $key => $val) {
                $keyPath = static::mergePaths($path, $key);
                if (is_array($val)) {
                    static::resolveIncludes($val, $indexFiles, $basedir, $keyPath);
                }

                $indexFiles[$keyPath] = $file;
                $schema[$key] = (is_array($val) && isset($schema[$key])) ? Util::mergeRecursive($val, $schema[$key]) : $val;
            }
        }
    }

    protected static function mergeInclude(array &$schema, $key, $file, $path, array &$indexFiles)
    {
        $arr = static::parseYaml($file);
        $basedir = dirname($file);
        $keyPath = static::mergePaths($path, $key);

        static::resolveIncludes($arr, $indexFiles, $basedir, $keyPath);

        $indexFiles[$keyPath] = $file;
        $schema[$key] = $arr;
    }

    protected static function resolveExtends(array &$schema, array $root = [], array &$indexFiles)
    {
        if (!$root) {
            $root = $schema;
        }

        $keyword = '+extends';
        foreach ($schema as $key => $value) {
            if ($key === $keyword) {
                $extends = (array) $value;
                foreach ($extends as $extendPath) {
                    if (!array_has($root, $extendPath)) {
                        throw new InvalidSchemaException("Cannot extend '{$extendPath}'. Key '{$extendPath}' is not defined in your schema.");
                    }

                    $valuesToExtend = array_get($root, $extendPath);
                    if (!is_array($valuesToExtend)) {
                        throw new InvalidSchemaException("Cannot extend '{$extendPath}'. Value of '{$extendPath}' is not an array.");
                    }

                    foreach ($valuesToExtend as $k => $v) {
                        $schema[$k] = (is_array($v) && isset($schema[$k])) ? Util::mergeRecursive($schema[$k], $v) : $v;
                    }
                }
                unset($schema[$keyword]);
            } elseif (is_array($value)) {
                static::resolveExtends($value, $root, $indexFiles);
                $schema[$key] = $value;
            }
        }
    }

    protected static function resolveVariables(array &$schema, array &$indexFiles, array $root = null, $path = '')
    {
        if (!$root) {
            $root = &$schema;
        }

        $fileSchema = static::getPathFileSchema($root, $indexFiles, $path);
        $schemaFile = static::getPathFile($indexFiles, $path);

        $rkey = "[a-z0-9_-]+";
        $varRegex = "/\\$\{(?<var>(?<this>this\.)?(?<key>$rkey(\.$rkey)*))}/i";

        foreach ($schema as $key => $value) {
            if (is_array($value)) {
                static::resolveVariables($value, $indexFiles, $root, static::mergePaths($path, $key));
                $schema[$key] = $value;
            }

            if (!is_string($value)) continue;

            preg_match_all($varRegex, $value, $matches);

            foreach ($matches['var'] as $i => $var) {
                $keyVar = $matches['key'][$i];
                $match = $matches[0][$i];
                $isThis = (bool) $matches['this'][$i];

                $runningPath = realpath('').'/';

                if ($isThis && !array_has($fileSchema, $keyVar)) {
                    throw new InvalidSchemaException("'{$keyVar}' is undefined in '{$schemaFile}'.");
                }

                if (!$isThis && !array_has($root, $keyVar)) {
                    $schemaFile = str_replace($runningPath, '', $root[self::KEY_FILE]);
                    throw new InvalidSchemaException("'{$keyVar}' is undefined in your schema.");
                }

                $varValue = $isThis ? array_get($fileSchema, $keyVar) : array_get($root, $keyVar);
                $schema[$key] = str_replace($match, $varValue, $schema[$key]);
            }
        }
    }

    protected static function resolveFile($dir, $file)
    {
        $file = $dir ? $dir.'/'.$file : $file;
        if (!ends_with($file, '.yml')) {
            $file .= '.yml';
        }
        return $file;
    }

    protected static function mergePaths()
    {
        return trim(implode('.', func_get_args()), '.');
    }

}
