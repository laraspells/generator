<?php

namespace LaraSpells\Generator;

class Util
{

    public static function mergeRecursive(array $arr1, array $arr2)
    {
        $arrDot = array_dot($arr2);
        foreach ($arrDot as $key => $value) {
            array_set($arr1, $key, $value);
        }
        return $arr1;
    }

}
