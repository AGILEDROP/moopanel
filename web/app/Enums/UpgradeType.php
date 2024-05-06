<?php

namespace App\Enums;

enum UpgradeType: int
{
    case Success = 0;
    case Notice = 1;
    case Error = 2;

    public function toReadableString(): string
    {
        return match ($this) {
            self::Success => __('Success'),
            self::Notice => __('Notice'),
            self::Error => __('Error')
        };
    }

    public function toDisplayColor(): string
    {
        return match ($this) {
            self::Success => 'success',
            self::Notice => 'warning',
            self::Error => 'danger',
        };
    }

    public function toDisplayIcon(): string
    {
        return match ($this) {
            self::Success => 'heroicon-o-check-circle',
            self::Notice => 'heroicon-o-information-circle',
            self::Error => 'heroicon-o-x-circle',
        };
    }
}
