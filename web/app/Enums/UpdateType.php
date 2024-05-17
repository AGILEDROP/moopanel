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

    public function getIconComponent(?string $class): string
    {
        return match ($this) {
            self::PLUGIN => '<x-fas-plug class="'.$class.'"></x-fas-plug>',
            self::CORE_MINOR, self::CORE_MAJOR => '<x-fas-cube class="'.$class.'"></x-fas-cube>',
            self::CORE_MEGA => '<x-fas-cubes class="'.$class.'"></x-fas-cube>',
        };
    }
}
