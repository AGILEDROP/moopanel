<?php

namespace App\Enums;

enum Role: string
{
    case MasterAdmin = '1';
    case User = '2';

    public function toReadableString(): string
    {
        return match ($this) {
            self::MasterAdmin => __('Master Admin'),
            self::User => __('User'),
        };
    }

    public function toDisplayColor(): string
    {
        return match ($this) {
            self::MasterAdmin => 'primary',
            self::User => 'gray',
        };
    }
}
