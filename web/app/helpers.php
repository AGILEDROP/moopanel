<?php

if (! function_exists('toLower')) {
    function toLower($string, $encoding = 'UTF-8'): string
    {
        return mb_strtolower($string, $encoding);
    }
}

if (! function_exists('toUpper')) {
    function toUpper($string, $encoding = 'UTF-8'): string
    {
        return mb_strtoupper($string, $encoding);
    }
}

if (! function_exists('firstToUpper')) {
    function firstToUpper($string, $encoding = 'UTF-8'): string
    {
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, null, $encoding);

        return mb_strtoupper($firstChar, $encoding).mb_strtolower($then, $encoding);
    }
}
