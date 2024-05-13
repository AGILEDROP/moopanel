<?php

namespace App\Enums;

enum UpdateType: string
{
    case PLUGIN = 'plugin';
    case CORE_MINOR = 'core-minor';
    case CORE_MAJOR = 'core-major';
    case CORE_MEGA = 'core-mega';

    public function toReadableString(): string
    {
        return match ($this) {
            self::PLUGIN => __('Plugin'),
            self::CORE_MINOR => __('Minor core'),
            self::CORE_MAJOR => __('Major core'),
            self::CORE_MEGA => __('Mega core'),
        };
    }

    public function getText(): string
    {
        return match ($this) {
            self::PLUGIN => __('Plugin update'),
            self::CORE_MINOR => __('Minor core update'),
            self::CORE_MAJOR => __('Major core update'),
            self::CORE_MEGA => __('Mega core update'),
        };
    }
}
