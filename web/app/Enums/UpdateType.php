<?php

namespace App\Enums;

enum UpdateType: string
{
    case PLUGIN = 'plugin';
    case MINOR_CORE = 'minor-core';
    case MAJOR_CORE = 'major-core';

    public function toReadableString(): string
    {
        return match ($this) {
            self::PLUGIN => __('Plugin'),
            self::MINOR_CORE => __('Minor core'),
            self::MAJOR_CORE => __('Major core'),
        };
    }

    public function getText(): string
    {
        return match ($this) {
            self::PLUGIN => __('Plugin update'),
            self::MINOR_CORE => __('Minor core update'),
            self::MAJOR_CORE => __('Major core update'),
        };
    }
}
