<?php

namespace App\Enums;

enum AccountTypes: string
{
    case Undefined = 'undefined';
    case Employee = 'employee';
    case Student = 'student';

    public function toReadableString(): string
    {
        return match ($this) {
            self::Undefined => __('Undefined'),
            self::Employee => __('Employee'),
            self::Student => __('Student')
        };
    }
}
