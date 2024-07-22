<?php

namespace App\Helpers;

class StringHelper
{
    public static function fileSizeConvert(int $bytes)
    {
        $bytes = floatval($bytes);
        $arbytes = [
            0 => [
                'UNIT' => 'TB',
                'VALUE' => pow(1024, 4),
            ],
            1 => [
                'UNIT' => 'GB',
                'VALUE' => pow(1024, 3),
            ],
            2 => [
                'UNIT' => 'MB',
                'VALUE' => pow(1024, 2),
            ],
            3 => [
                'UNIT' => 'KB',
                'VALUE' => 1024,
            ],
            4 => [
                'UNIT' => 'B',
                'VALUE' => 1,
            ],
        ];

        $result = '';

        foreach ($arbytes as $aritem) {
            if ($bytes >= $aritem['VALUE']) {
                $result = $bytes / $aritem['VALUE'];
                $result = str_replace('.', ',', strval(round($result, 2)));
                $result .= ' '.$aritem['UNIT'];
                break;
            }
        }

        return $result;
    }
}
