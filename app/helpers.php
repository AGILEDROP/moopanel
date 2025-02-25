<?php

use Carbon\Carbon;

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

if (! function_exists('dateToUnixOrNull')) {
    function dateToUnixOrNull(?string $time, string $format = 'Y-m-d'): ?int
    {
        if (isset($time)) {
            $unix = Carbon::createFromFormat($format, $time, config('app.timezone'))->unix();
        }

        return $unix ?? null;
    }
}

if (! function_exists('stripUrlPath')) {
    function stripUrlPath(string $url): string
    {
        $urlParts = parse_url(trim($url));

        return $urlParts['scheme'].'://'.$urlParts['host'].'/';
    }
}

if (! function_exists('wrapToDataArray')) {
    function wrapData(array $data): array
    {
        return [
            'data' => $data,
        ];
    }
}
