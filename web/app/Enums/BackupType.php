<?php

namespace App\Enums;

enum BackupType: string
{
    case INSTANCE = 'instance';
    case COURSE = 'course';

    public function toReadableString(): string
    {
        return match ($this) {
            self::INSTANCE => __('Instance'),
            self::COURSE => __('Course'),
        };
    }

    public function getText(): string
    {
        return match ($this) {
            self::INSTANCE => __('Instances backup'),
            self::COURSE => __('Courses backup'),
        };
    }

    public function getIconComponent(?string $class): string
    {
        return match ($this) {
            self::INSTANCE => '<x-fas-database class="'.$class.'"></x-fas-database>',
            self::COURSE => '<x-fas-box-archive class="'.$class.'"></x-fas-box-archive>',
        };
    }
}
