<?php

namespace App\Enums;

enum UpdateMaturity: string
{
    case ALPHA = '50';
    case BETA = '100';
    case RC = '150';
    case STABLE = '200';
    case ANY = 'any';

    public function toReadableString(): string
    {
        return match ($this) {
            self::ALPHA => __('Alpha'),
            self::BETA => __('Beta'),
            self::RC => __('RC'),
            self::STABLE => __('Stable'),
            self::ANY => __('Any'),
        };
    }

    public function getDisplayColor(): string
    {
        return match ($this) {
            self::ALPHA, self::BETA, self::RC => 'gray',
            self::STABLE => 'success',
            self::ANY => 'warning',
        };
    }

    public function getMaturityDescription(): string
    {
        return match ($this) {
            self::ALPHA => __('Intervals can be tested using white box techniques.'),
            self::BETA => __('Feature complete, ready for preview and testing.'),
            self::RC => __('Tested, will be released unless there are fatal bugs.'),
            self::STABLE => __('Ready for production deployment.'),
            self::ANY => __('Special value that can be used in $plugin->dependencies in version.php files.'),
        };
    }
}
